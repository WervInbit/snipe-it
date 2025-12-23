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
