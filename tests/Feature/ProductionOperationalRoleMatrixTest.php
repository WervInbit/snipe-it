<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\ComponentDefinition;
use App\Models\ComponentInstance;
use App\Models\Group;
use App\Models\Setting;
use App\Models\TestType;
use App\Models\User;
use App\Models\WorkOrder;
use Database\Seeders\ProductionPermissionGroupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductionOperationalRoleMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_foundation_role_matrix_uses_explicit_operational_permissions(): void
    {
        $this->seed(ProductionPermissionGroupSeeder::class);

        $refurbisher = $this->userInGroup('Refurbisher');
        $senior = $this->userInGroup('Senior Refurbisher');
        $supervisor = $this->userInGroup('Supervisor');
        $admin = $this->userInGroup('Admin');
        $component = ComponentInstance::factory()->create();

        foreach ([$refurbisher, $senior, $supervisor] as $operator) {
            $this->assertTrue($operator->hasAccess('assets.view'));
            $this->assertTrue($operator->hasAccess('assets.edit'));
            $this->assertTrue($operator->hasAccess('assets.images.upload'));
            $this->assertTrue($operator->hasAccess('scanning'));
            $this->assertTrue(Gate::forUser($operator)->allows('view', ComponentInstance::class));
            $this->assertTrue(Gate::forUser($operator)->allows('create', ComponentInstance::class));
            $this->assertTrue(Gate::forUser($operator)->allows('extract', $component));
            $this->assertTrue(Gate::forUser($operator)->allows('install', $component));
            $this->assertTrue(Gate::forUser($operator)->allows('move', $component));
            $this->assertTrue(Gate::forUser($operator)->allows('verify', $component));
        }

        $this->assertFalse($refurbisher->hasAccess('tests.execute'));
        $this->assertFalse(Gate::forUser($refurbisher)->allows('tests.execute'));
        $this->assertFalse($refurbisher->hasAccess('assets.images.manage'));
        $this->assertFalse(Gate::forUser($refurbisher)->allows('create', WorkOrder::class));

        $this->assertTrue($senior->hasAccess('tests.execute'));
        $this->assertTrue(Gate::forUser($senior)->allows('tests.execute'));
        $this->assertTrue($senior->hasAccess('assets.images.manage'));

        foreach ([$refurbisher, $senior] as $operator) {
            $this->assertFalse($operator->hasAccess('assets.sale_transition'));
            $this->assertFalse(Gate::forUser($operator)->allows('delete', $component));
            $this->assertFalse(Gate::forUser($operator)->allows('destroy', $component));
            $this->assertFalse(Gate::forUser($operator)->allows('viewAny', WorkOrder::class));
            $this->assertFalse(Gate::forUser($operator)->allows('create', WorkOrder::class));
            $this->assertFalse(Gate::forUser($operator)->allows('update', new WorkOrder()));
            $this->assertFalse(Gate::forUser($operator)->allows('manageVisibility', new WorkOrder()));
            $this->assertFalse($operator->hasAccess('components.manage_definitions'));
            $this->assertFalse($operator->hasAccess('components.manage_definition_lifecycle'));
            $this->assertFalse($operator->hasAccess('components.manage_storage_locations'));
            $this->assertFalse(Gate::forUser($operator)->allows('view', AssetModel::class));
            $this->assertFalse(Gate::forUser($operator)->allows('viewAny', AttributeDefinition::class));
            $this->assertFalse(Gate::forUser($operator)->allows('index', TestType::class));
        }

        $this->assertTrue($supervisor->hasAccess('assets.sale_transition'));
        $this->assertTrue(Gate::forUser($supervisor)->allows('tests.execute'));
        $this->assertTrue(Gate::forUser($supervisor)->allows('assets.sale_transition'));
        $this->assertTrue(Gate::forUser($supervisor)->allows('delete', $component));
        $this->assertTrue(Gate::forUser($supervisor)->allows('destroy', $component));
        $this->assertTrue(Gate::forUser($supervisor)->allows('viewAny', WorkOrder::class));
        $this->assertTrue(Gate::forUser($supervisor)->allows('create', WorkOrder::class));
        $this->assertTrue(Gate::forUser($supervisor)->allows('update', new WorkOrder()));
        $this->assertTrue(Gate::forUser($supervisor)->allows('manageVisibility', new WorkOrder()));
        $this->assertTrue(Gate::forUser($supervisor)->allows('view', AssetModel::class));
        $this->assertTrue(Gate::forUser($supervisor)->allows('create', AssetModel::class));
        $this->assertTrue(Gate::forUser($supervisor)->allows('update', AssetModel::class));
        $this->assertFalse(Gate::forUser($supervisor)->allows('delete', AssetModel::class));
        $this->assertFalse(Gate::forUser($supervisor)->allows('manageLifecycle', AssetModel::class));
        $this->assertFalse(Gate::forUser($supervisor)->allows('manageSpecificationCleanup', AssetModel::class));
        $this->assertTrue(Gate::forUser($supervisor)->allows('viewAny', AttributeDefinition::class));
        $this->assertTrue(Gate::forUser($supervisor)->allows('create', AttributeDefinition::class));
        $this->assertFalse(Gate::forUser($supervisor)->allows('delete', AttributeDefinition::class));
        $this->assertFalse(Gate::forUser($supervisor)->allows('manageLifecycle', new AttributeDefinition()));
        $this->assertTrue(Gate::forUser($supervisor)->allows('index', TestType::class));
        $this->assertTrue(Gate::forUser($supervisor)->allows('create', TestType::class));
        $this->assertTrue(Gate::forUser($supervisor)->allows('update', TestType::class));
        $this->assertFalse(Gate::forUser($supervisor)->allows('delete', TestType::class));
        $this->assertTrue($supervisor->hasAccess('components.manage_definitions'));
        $this->assertFalse($supervisor->hasAccess('components.manage_definition_lifecycle'));
        $this->assertFalse($supervisor->hasAccess('components.manage_storage_locations'));

        $this->assertTrue($admin->hasAccess('components.manage_definitions'));
        $this->assertTrue($admin->hasAccess('components.manage_definition_lifecycle'));
        $this->assertTrue($admin->hasAccess('components.manage_storage_locations'));
        $this->assertTrue($admin->hasAccess('models.manage_lifecycle'));
        $this->assertTrue($admin->hasAccess('models.manage_specification_cleanup'));
        $this->assertTrue($admin->hasAccess('attributes.lifecycle'));
        $this->assertTrue($admin->hasAccess('workflows.delete'));
        $this->assertTrue($admin->hasAccess('config.manage'));

        foreach (['Refurbisher', 'Senior Refurbisher', 'Supervisor'] as $groupName) {
            $permissions = $this->groupPermissions($groupName);
            $this->assertArrayNotHasKey('refurbisher', $permissions);
            $this->assertArrayNotHasKey('senior-refurbisher', $permissions);
            $this->assertArrayNotHasKey('supervisor', $permissions);
            $this->assertArrayNotHasKey('admin', $permissions);
        }
    }

    public function test_seeded_floor_roles_can_reach_their_scan_component_work_order_and_image_routes(): void
    {
        Setting::factory()->create();
        $this->seed(ProductionPermissionGroupSeeder::class);
        Storage::fake('public');

        foreach (['Refurbisher', 'Senior Refurbisher', 'Supervisor'] as $groupName) {
            $user = $this->userInGroup($groupName);
            $asset = Asset::factory()->create();

            $this->actingAs($user)
                ->get(route('scan'))
                ->assertOk();
            $this->actingAs($user)
                ->get(route('components.index'))
                ->assertOk();
            $this->actingAs($user)
                ->post(route('asset-images.store', $asset), [
                    'image' => [UploadedFile::fake()->image($groupName.'.jpg')],
                    'caption' => [$groupName.' image'],
                ])
                ->assertRedirect();

            $workOrderResponse = $this->actingAs($user)->get(route('work-orders.index'));

            if ($groupName === 'Supervisor') {
                $workOrderResponse->assertOk();
            } else {
                $workOrderResponse->assertForbidden();
            }
        }
    }

    public function test_foundation_role_rerun_merges_required_grants_without_removing_custom_permissions(): void
    {
        Group::factory()->create([
            'name' => 'Supervisor',
            'permissions' => json_encode([
                'operator.custom_permission' => 1,
                'models.delete' => 1,
                'assets.view' => -1,
            ]),
        ]);

        $this->seed(ProductionPermissionGroupSeeder::class);

        $permissions = $this->groupPermissions('Supervisor');
        $this->assertSame(1, $permissions['operator.custom_permission']);
        $this->assertSame(1, $permissions['models.delete']);
        $this->assertSame(1, $permissions['assets.view']);
        $this->assertSame(1, $permissions['components.destroy']);
        $this->assertSame(1, $permissions['models.create']);
        $this->assertSame(1, $permissions['attributes.create']);
        $this->assertSame(1, $permissions['components.manage_definitions']);
        $this->assertSame(1, $permissions['workflows.edit']);
        $this->assertArrayNotHasKey('models.manage_lifecycle', $permissions);
        $this->assertArrayNotHasKey('models.manage_specification_cleanup', $permissions);
        $this->assertArrayNotHasKey('attributes.lifecycle', $permissions);
        $this->assertArrayNotHasKey('components.manage_definition_lifecycle', $permissions);
        $this->assertArrayNotHasKey('workflows.delete', $permissions);
    }

    private function userInGroup(string $groupName): User
    {
        $user = User::factory()->create(['permissions' => '{}']);
        $user->groups()->attach(Group::query()->where('name', $groupName)->firstOrFail());

        return $user->fresh();
    }

    /**
     * @return array<string,int>
     */
    private function groupPermissions(string $groupName): array
    {
        return json_decode(
            (string) Group::query()->where('name', $groupName)->firstOrFail()->permissions,
            true
        );
    }
}
