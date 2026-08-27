<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\Category;
use App\Models\ComponentDefinition;
use App\Models\Group;
use App\Models\ModelNumberAttribute;
use App\Models\Setting;
use App\Models\TestType;
use App\Models\User;
use App\Models\WorkflowProfile;
use Database\Seeders\ProductionPermissionGroupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupervisorCatalogAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        Setting::factory()->create();
        $this->seed(ProductionPermissionGroupSeeder::class);
    }

    public function test_catalog_and_workflow_permissions_are_registered(): void
    {
        $registered = collect(config('permissions'))
            ->flatten(1)
            ->pluck('permission')
            ->filter()
            ->values();

        foreach ([
            'models.manage_lifecycle',
            'models.manage_specification_cleanup',
            'attributes.view',
            'attributes.create',
            'attributes.edit',
            'attributes.lifecycle',
            'attributes.delete',
            'components.manage_definition_lifecycle',
            'workflows.view',
            'workflows.create',
            'workflows.edit',
            'workflows.delete',
        ] as $permission) {
            $this->assertTrue($registered->contains($permission), "Missing registered permission {$permission}");
        }

        $this->assertFalse($registered->contains('test_types.view'));
    }

    public function test_supervisor_can_reach_setup_pages_while_lower_roles_cannot(): void
    {
        $supervisor = $this->userInGroup('Supervisor');
        $senior = $this->userInGroup('Senior Refurbisher');

        foreach ([
            route('models.index'),
            route('settings.model_numbers.index'),
            route('attributes.index'),
            route('settings.component_definitions.index'),
            route('settings.workflow-profiles.index'),
            route('settings.testtypes.index'),
        ] as $url) {
            $this->actingAs($supervisor)->get($url)->assertOk();
            $this->actingAs($senior)->get($url)->assertForbidden();
        }

        $this->actingAs($supervisor)
            ->get(route('settings.model_numbers.index'))
            ->assertSee(route('settings.model_numbers.create'), false);

        $this->actingAsForApi($senior)
            ->postJson(route('api.models.store'), [
                'name' => 'Forbidden Senior Model',
                'category_id' => Category::factory()->assetLaptopCategory()->create()->id,
            ])
            ->assertForbidden();
    }

    public function test_supervisor_can_build_normal_catalog_and_workflow_definitions(): void
    {
        $supervisor = $this->userInGroup('Supervisor');
        $assetCategory = Category::factory()->assetLaptopCategory()->create();

        $this->actingAs($supervisor)
            ->post(route('models.store'), [
                'name' => 'Supervisor Product',
                'category_id' => $assetCategory->id,
            ])
            ->assertRedirect();

        $model = AssetModel::query()->where('name', 'Supervisor Product')->firstOrFail();

        $this->actingAs($supervisor)
            ->post(route('models.numbers.store', $model), [
                'code' => 'SUP-001',
                'label' => 'Supervisor Variant',
            ])
            ->assertRedirect(route('models.show', $model));

        $this->actingAs($supervisor)
            ->post(route('attributes.store'), [
                'label' => 'Supervisor Attribute',
                'datatype' => AttributeDefinition::DATATYPE_TEXT,
            ])
            ->assertRedirect();

        $this->actingAs($supervisor)
            ->post(route('settings.component_definitions.store'), [
                'name' => 'Supervisor Component Definition',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->actingAs($supervisor)
            ->post(route('settings.testtypes.store'), [
                'name' => 'Supervisor Workflow Item',
                'is_required' => '1',
            ])
            ->assertRedirect(route('settings.testtypes.index'));

        $this->actingAs($supervisor)
            ->post(route('settings.workflow-profiles.store'), [
                'name' => 'Supervisor Workflow Profile',
                'is_active' => '1',
            ])
            ->assertRedirect(route('settings.workflow-profiles.index'));

        $this->assertDatabaseHas('model_numbers', ['model_id' => $model->id, 'code' => 'SUP-001']);
        $this->assertDatabaseHas('attribute_definitions', ['label' => 'Supervisor Attribute']);
        $this->assertDatabaseHas('component_definitions', ['name' => 'Supervisor Component Definition']);
        $this->assertDatabaseHas('workflow_items', ['name' => 'Supervisor Workflow Item']);
        $this->assertDatabaseHas('workflow_profiles', ['name' => 'Supervisor Workflow Profile']);
    }

    public function test_supervisor_cannot_use_catalog_lifecycle_or_cleanup_routes(): void
    {
        $supervisor = $this->userInGroup('Supervisor');
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $attribute = AttributeDefinition::create([
            'key' => 'supervisor_cleanup_guard',
            'label' => 'Supervisor Cleanup Guard',
            'datatype' => AttributeDefinition::DATATYPE_ENUM,
            'allow_asset_override' => true,
        ]);
        $option = $attribute->options()->create([
            'value' => 'guarded',
            'label' => 'Guarded',
            'active' => true,
            'sort_order' => 0,
        ]);
        $assignment = ModelNumberAttribute::create([
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $attribute->id,
            'display_order' => 0,
        ]);
        $componentDefinition = ComponentDefinition::factory()->create(['is_active' => true]);
        $workflowItem = TestType::factory()->create();
        $workflowProfile = WorkflowProfile::factory()->create();

        $this->actingAs($supervisor)
            ->patch(route('models.numbers.deprecate', [$model, $modelNumber]))
            ->assertForbidden();
        $this->actingAs($supervisor)
            ->put(route('models.numbers.update', [$model, $modelNumber]), [
                'code' => $modelNumber->code,
                'label' => $modelNumber->label,
                'status' => 'deprecated',
            ])
            ->assertForbidden();
        $this->actingAs($supervisor)
            ->put(route('settings.model_numbers.update', $modelNumber), [
                'code' => $modelNumber->code,
                'label' => $modelNumber->label,
                'status' => 'deprecated',
            ])
            ->assertForbidden();
        $this->actingAs($supervisor, 'web')
            ->delete(route('models.numbers.destroy', [$model, $modelNumber]))
            ->assertForbidden();
        $this->actingAs($supervisor)
            ->deleteJson(route('models.numbers.attributes.destroy', [$model, $modelNumber, $attribute]))
            ->assertForbidden();
        $this->actingAs($supervisor)
            ->put(route('models.numbers.spec.update', [$model, $modelNumber]), [
                'model_number_id' => $modelNumber->id,
                'attribute_order' => [],
                'attributes' => [],
                'component_templates' => [],
            ])
            ->assertForbidden();
        $this->actingAs($supervisor)
            ->patch(route('attributes.hide', $attribute))
            ->assertForbidden();
        $this->actingAs($supervisor)
            ->put(route('attributes.update', $attribute), [
                'key' => $attribute->key,
                'label' => $attribute->label,
                'datatype' => $attribute->datatype,
                'options' => [
                    'existing' => [
                        $option->id => [
                            'value' => $option->value,
                            'label' => $option->label,
                            'active' => 1,
                            'delete' => 1,
                        ],
                    ],
                ],
            ])
            ->assertForbidden();
        $this->actingAs($supervisor)
            ->patch(route('settings.component_definitions.deactivate', $componentDefinition))
            ->assertForbidden();
        $this->actingAs($supervisor)
            ->put(route('settings.component_definitions.update', $componentDefinition), [
                'name' => $componentDefinition->name,
                'is_active' => '0',
            ])
            ->assertForbidden();
        $this->actingAs($supervisor)
            ->delete(route('settings.testtypes.destroy', $workflowItem))
            ->assertForbidden();
        $this->actingAs($supervisor)
            ->delete(route('settings.workflow-profiles.destroy', $workflowProfile))
            ->assertForbidden();
        $this->actingAsForApi($supervisor)
            ->deleteJson(route('api.models.destroy', $model))
            ->assertForbidden();

        $this->assertDatabaseHas('model_number_attributes', ['id' => $assignment->id]);
        $this->assertDatabaseHas('component_definitions', [
            'id' => $componentDefinition->id,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('workflow_items', ['id' => $workflowItem->id]);
        $this->assertDatabaseHas('workflow_profiles', ['id' => $workflowProfile->id]);
    }

    public function test_preserved_legacy_model_delete_grant_does_not_bypass_lifecycle_boundary(): void
    {
        $group = Group::query()->where('name', 'Supervisor')->firstOrFail();
        $permissions = $group->decodePermissions();
        $permissions['models.delete'] = 1;
        $group->update(['permissions' => json_encode($permissions)]);

        $supervisor = $this->userInGroup('Supervisor');
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();

        $this->actingAs($supervisor)
            ->delete(route('models.destroy', $model))
            ->assertForbidden();
        $this->actingAsForApi($supervisor)
            ->deleteJson(route('api.models.destroy', $model))
            ->assertForbidden();
        $this->actingAs($supervisor, 'web')
            ->delete(route('models.numbers.destroy', [$model, $modelNumber]))
            ->assertForbidden();

        $deletedModel = AssetModel::factory()->create();
        $deletedModel->delete();

        $this->actingAs($supervisor, 'web')
            ->post(route('models.restore.store', $deletedModel->id))
            ->assertForbidden();

        $this->assertNotSoftDeleted($model);
        $this->assertSoftDeleted($deletedModel);
    }

    private function userInGroup(string $groupName): User
    {
        $user = User::factory()->create(['permissions' => '{}']);
        $user->groups()->attach(Group::query()->where('name', $groupName)->firstOrFail());

        return $user->fresh();
    }
}
