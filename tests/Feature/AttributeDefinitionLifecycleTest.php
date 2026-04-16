<?php

namespace Tests\Feature;

use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\ModelNumberAttribute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributeDefinitionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function makeSuperUser(): User
    {
        return User::factory()->superuser()->create();
    }

    private function makeAttribute(array $overrides = []): AttributeDefinition
    {
        $definition = AttributeDefinition::create(array_merge([
            'key' => 'attr_'.uniqid(),
            'label' => 'Example Attribute',
            'datatype' => AttributeDefinition::DATATYPE_TEXT,
            'unit' => null,
            'required_for_category' => false,
            'allow_custom_values' => false,
            'allow_asset_override' => true,
        ], $overrides));

        return $definition;
    }

    public function test_create_auto_generates_key_from_label_when_manual_override_is_off(): void
    {
        $user = $this->makeSuperUser();

        $this->actingAs($user)
            ->post(route('attributes.store'), [
                'label' => 'Battery Health',
                'datatype' => AttributeDefinition::DATATYPE_TEXT,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('attribute_definitions', [
            'label' => 'Battery Health',
            'key' => 'battery_health',
        ]);
    }

    public function test_create_auto_key_appends_numeric_suffix_on_collision(): void
    {
        $user = $this->makeSuperUser();
        $this->makeAttribute([
            'label' => 'Battery Health Existing',
            'key' => 'battery_health',
        ]);

        $this->actingAs($user)
            ->post(route('attributes.store'), [
                'label' => 'Battery Health',
                'datatype' => AttributeDefinition::DATATYPE_TEXT,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('attribute_definitions', [
            'label' => 'Battery Health',
            'key' => 'battery_health_2',
        ]);
    }

    public function test_create_manual_override_sanitizes_key_and_applies_suffix_on_collision(): void
    {
        $user = $this->makeSuperUser();
        $this->makeAttribute([
            'label' => 'Other Attribute',
            'key' => 'custom_key',
        ]);

        $this->actingAs($user)
            ->post(route('attributes.store'), [
                'label' => 'Any Label',
                'key' => 'Custom Key !!!',
                'manual_key_override' => 1,
                'datatype' => AttributeDefinition::DATATYPE_TEXT,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('attribute_definitions', [
            'label' => 'Any Label',
            'key' => 'custom_key_2',
        ]);
    }

    public function test_superuser_can_create_new_attribute_version(): void
    {
        $user = $this->makeSuperUser();
        $attribute = $this->makeAttribute();
        $attribute->options()->create([
            'value' => 'foo',
            'label' => 'Foo',
            'active' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($user)
            ->post(route('attributes.versions.store', $attribute), [
                'label' => 'Example Attribute v2',
                'datatype' => AttributeDefinition::DATATYPE_ENUM,
                'allow_custom_values' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $attribute->refresh();
        $this->assertNotNull($attribute->deprecated_at);
        $this->assertNotNull($attribute->hidden_at);

        $new = AttributeDefinition::where('key', $attribute->key)
            ->whereNull('deprecated_at')
            ->first();

        $this->assertNotNull($new);
        $this->assertSame($attribute->id, $new->supersedes_attribute_id);
        $this->assertSame(2, $new->version);
        $this->assertSame(AttributeDefinition::DATATYPE_ENUM, $new->datatype);
        $this->assertTrue($new->allow_custom_values);
        $this->assertEquals(1, $new->options()->count());
    }

    public function test_create_version_form_uses_drag_handles_instead_of_manual_sort_input(): void
    {
        $user = $this->makeSuperUser();
        $attribute = $this->makeAttribute([
            'datatype' => AttributeDefinition::DATATYPE_ENUM,
        ]);
        $attribute->options()->create([
            'value' => 'excellent',
            'label' => 'Excellent',
            'active' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('attributes.versions.create', $attribute))
            ->assertOk()
            ->assertSee('data-option-drag-handle', false)
            ->assertDontSee('id="new_option_sort"', false)
            ->assertSee(trans('attribute_definitions.unsaved_option_confirm'));
    }

    public function test_create_version_assigns_sequential_sort_order_when_sort_values_are_omitted(): void
    {
        $user = $this->makeSuperUser();
        $attribute = $this->makeAttribute([
            'datatype' => AttributeDefinition::DATATYPE_ENUM,
        ]);

        $this->actingAs($user)
            ->post(route('attributes.versions.store', $attribute), [
                'label' => 'Condition',
                'datatype' => AttributeDefinition::DATATYPE_ENUM,
                'options' => [
                    'new' => [
                        ['value' => 'grade_a', 'label' => 'Grade A', 'active' => 1],
                        ['value' => 'grade_b', 'label' => 'Grade B', 'active' => 1],
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $new = AttributeDefinition::where('key', $attribute->key)
            ->whereNull('deprecated_at')
            ->firstOrFail();

        $ordered = $new->options()->orderBy('sort_order')->get();

        $this->assertSame(['grade_a', 'grade_b'], $ordered->pluck('value')->all());
        $this->assertSame([0, 1], $ordered->pluck('sort_order')->map(fn ($value) => (int) $value)->all());
    }

    public function test_cannot_change_datatype_in_place(): void
    {
        $user = $this->makeSuperUser();
        $attribute = $this->makeAttribute();

        $this->actingAs($user)
            ->put(route('attributes.update', $attribute), [
                'label' => 'Changed Label',
                'key' => $attribute->key,
                'datatype' => AttributeDefinition::DATATYPE_ENUM,
            ])
            ->assertSessionHasErrors(['datatype']);
    }

    public function test_cannot_change_key_in_place(): void
    {
        $user = $this->makeSuperUser();
        $attribute = $this->makeAttribute();

        $this->actingAs($user)
            ->put(route('attributes.update', $attribute), [
                'label' => 'Changed Label',
                'key' => 'new_key_value',
                'datatype' => $attribute->datatype,
            ])
            ->assertSessionHasErrors(['key']);
    }

    public function test_hide_and_unhide_attribute(): void
    {
        $user = $this->makeSuperUser();
        $attribute = $this->makeAttribute();

        $this->actingAs($user)
            ->patch(route('attributes.hide', $attribute))
            ->assertRedirect()
            ->assertSessionHas('success');

        $attribute->refresh();
        $this->assertNotNull($attribute->hidden_at);

        $this->actingAs($user)
            ->patch(route('attributes.unhide', $attribute))
            ->assertRedirect()
            ->assertSessionHas('success');

        $attribute->refresh();
        $this->assertNull($attribute->hidden_at);
    }

    public function test_cannot_unhide_deprecated_attribute(): void
    {
        $user = $this->makeSuperUser();
        $attribute = $this->makeAttribute();
        $attribute->forceFill(['deprecated_at' => now(), 'hidden_at' => now()])->save();

        $this->actingAs($user)
            ->patch(route('attributes.unhide', $attribute))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_cannot_delete_attribute_in_use(): void
    {
        $user = $this->makeSuperUser();
        $attribute = $this->makeAttribute();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();

        ModelNumberAttribute::create([
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $attribute->id,
            'display_order' => 0,
        ]);

        $this->actingAs($user)
            ->delete(route('attributes.destroy', $attribute))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('attribute_definitions', [
            'id' => $attribute->id,
            'deleted_at' => null,
        ]);
    }
}
