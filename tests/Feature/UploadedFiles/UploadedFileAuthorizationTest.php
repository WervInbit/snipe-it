<?php

namespace Tests\Feature\UploadedFiles;

use App\Http\Controllers\Controller;
use App\Models\Accessory;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Company;
use App\Models\Component;
use App\Models\ComponentInstance;
use App\Models\Consumable;
use App\Models\License;
use App\Models\Location;
use App\Models\Maintenance;
use App\Models\ModelNumber;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadedFileAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.default'));
        $this->withHeader('Accept', 'application/json');
    }

    public function testEverySupportedParentHasARegisteredPolicyWithSeparateFileAbilities(): void
    {
        $expectedTypes = [
            Accessory::class,
            Asset::class,
            AssetModel::class,
            Component::class,
            ComponentInstance::class,
            Consumable::class,
            License::class,
            Location::class,
            Maintenance::class,
            ModelNumber::class,
            User::class,
            WorkOrder::class,
        ];

        $this->assertSame(
            array_keys(Controller::$map_object_type),
            array_keys(Controller::$map_storage_path)
        );
        $this->assertSame(
            array_keys(Controller::$map_object_type),
            array_keys(Controller::$map_file_prefix)
        );

        foreach ($expectedTypes as $modelClass) {
            $policy = Gate::getPolicyFor($modelClass);

            $this->assertNotNull($policy, "No attachment policy is registered for {$modelClass}.");
            $this->assertTrue(method_exists($policy, 'viewFiles'));
            $this->assertTrue(method_exists($policy, 'createFiles'));
            $this->assertTrue(method_exists($policy, 'deleteFiles'));
        }

        $this->assertEqualsCanonicalizing(
            $expectedTypes,
            array_values(array_unique(Controller::$map_object_type))
        );
    }

    public function testOrdinaryParentsUseViewForReadsAndUpdateForMutations(): void
    {
        $assetModel = AssetModel::factory()->create();
        $component = Component::factory()->create();
        $componentInstance = ComponentInstance::factory()->create();
        $location = Location::factory()->create();
        $modelNumber = ModelNumber::factory()->for($assetModel, 'model')->create();
        $targetUser = User::factory()->create();
        $workOrder = WorkOrder::factory()->create();
        $viewer = $this->userWithPermissions([
            'models.view',
            'components.view',
            'locations.view',
            'users.view',
            'workorders.view',
        ]);
        $mutator = $this->userWithPermissions([
            'models.edit',
            'components.update',
            'locations.edit',
            'users.edit',
            'workorders.update',
        ]);

        $parents = [
            'component' => $component,
            'component instance' => $componentInstance,
            'location' => $location,
            'model number' => $modelNumber,
            'user' => $targetUser,
            'work order' => $workOrder,
        ];

        foreach ($parents as $label => $parent) {
            $this->assertTrue(
                Gate::forUser($viewer)->allows('viewFiles', $parent),
                "A read-only user should be able to read {$label} files."
            );
            $this->assertFalse(
                Gate::forUser($viewer)->allows('createFiles', $parent),
                "A read-only user must not create {$label} files."
            );
            $this->assertFalse(
                Gate::forUser($viewer)->allows('deleteFiles', $parent),
                "A read-only user must not delete {$label} files."
            );
            $this->assertTrue(
                Gate::forUser($mutator)->allows('viewFiles', $parent),
                "A mutator should be able to read {$label} files."
            );
            $this->assertTrue(
                Gate::forUser($mutator)->allows('createFiles', $parent),
                "A mutator should be able to create {$label} files."
            );
            $this->assertTrue(
                Gate::forUser($mutator)->allows('deleteFiles', $parent),
                "A mutator should be able to delete {$label} files."
            );
        }
    }

    public function testAssetFilePermissionsAreIndependentFromOrdinaryAssetAccess(): void
    {
        $asset = Asset::factory()->create();
        $uploader = $this->userWithPermissions(['assets.files.upload']);
        $fileViewer = $this->userWithPermissions(['assets.files.view']);
        $fileManager = $this->userWithPermissions(['assets.files.manage']);
        $ordinaryViewer = $this->userWithPermissions(['assets.view']);
        $ordinaryEditor = $this->userWithPermissions(['assets.edit']);
        $log = $this->uploadAs($uploader, 'assets', $asset);

        foreach ([$ordinaryViewer, $ordinaryEditor] as $ordinaryUser) {
            $this->actingAsForApi($ordinaryUser)
                ->getJson(route('api.files.index', ['object_type' => 'assets', 'id' => $asset->id]))
                ->assertForbidden();

            $this->actingAsForApi($ordinaryUser)
                ->get(route('api.files.show', [
                    'object_type' => 'assets',
                    'id' => $asset->id,
                    'file_id' => $log->id,
                ]))
                ->assertForbidden();
        }

        $this->actingAsForApi($fileViewer)
            ->getJson(route('api.files.index', ['object_type' => 'assets', 'id' => $asset->id]))
            ->assertOk()
            ->assertJsonPath('total', 1);

        $this->actingAsForApi($fileViewer)
            ->get(route('api.files.show', [
                'object_type' => 'assets',
                'id' => $asset->id,
                'file_id' => $log->id,
            ]))
            ->assertOk();

        $this->actingAsForApi($fileViewer)
            ->post(route('api.files.store', ['object_type' => 'assets', 'id' => $asset->id]), [
                'file' => [$this->attachment()],
            ])
            ->assertForbidden();

        $this->actingAsForApi($fileViewer)
            ->delete(route('api.files.destroy', [
                'object_type' => 'assets',
                'id' => $asset->id,
                'file_id' => $log->id,
            ]))
            ->assertForbidden();

        $this->actingAs($fileViewer, 'web')
            ->post(route('ui.files.store', ['object_type' => 'assets', 'id' => $asset->id]), [
                'file' => [$this->attachment()],
            ])
            ->assertForbidden();

        $this->actingAs($fileViewer, 'web')
            ->delete(route('ui.files.destroy', [
                'object_type' => 'assets',
                'id' => $asset->id,
                'file_id' => $log->id,
            ]))
            ->assertForbidden();

        $this->actingAsForApi($fileManager)
            ->delete(route('api.files.destroy', [
                'object_type' => 'assets',
                'id' => $asset->id,
                'file_id' => $log->id,
            ]))
            ->assertOk()
            ->assertStatusMessageIs('success');

        $this->assertSoftDeleted('action_logs', ['id' => $log->id]);
        Storage::disk(config('filesystems.default'))->assertMissing($log->uploads_file_path());
    }

    public function testAssetModelFilePermissionsAreIndependentFromOrdinaryModelAccess(): void
    {
        $model = AssetModel::factory()->create();
        $uploader = $this->userWithPermissions(['models.files.upload']);
        $fileViewer = $this->userWithPermissions(['models.files.view']);
        $ordinaryModelManager = $this->userWithPermissions(['models.view', 'models.edit']);
        $log = $this->uploadAs($uploader, 'models', $model);

        $this->actingAsForApi($ordinaryModelManager)
            ->getJson(route('api.files.index', ['object_type' => 'models', 'id' => $model->id]))
            ->assertForbidden();

        $this->actingAsForApi($fileViewer)
            ->getJson(route('api.files.index', ['object_type' => 'models', 'id' => $model->id]))
            ->assertOk()
            ->assertJsonPath('total', 1);

        $this->actingAsForApi($fileViewer)
            ->get(route('api.files.show', [
                'object_type' => 'models',
                'id' => $model->id,
                'file_id' => $log->id,
            ]))
            ->assertOk();
    }

    public function testComponentUpdaterHasLeastPrivilegeFileAccessWhileViewerCannotMutate(): void
    {
        $component = ComponentInstance::factory()->create();
        $updater = $this->userWithPermissions(['components.update']);
        $viewer = $this->userWithPermissions(['components.view']);
        $log = $this->uploadAs($updater, 'component-instances', $component);

        $this->actingAsForApi($viewer)
            ->getJson(route('api.files.index', [
                'object_type' => 'component-instances',
                'id' => $component->id,
            ]))
            ->assertOk()
            ->assertJsonPath('total', 1);

        $this->actingAsForApi($viewer)
            ->post(route('api.files.store', [
                'object_type' => 'component-instances',
                'id' => $component->id,
            ]), ['file' => [$this->attachment()]])
            ->assertForbidden();

        $this->actingAsForApi($viewer)
            ->delete(route('api.files.destroy', [
                'object_type' => 'component-instances',
                'id' => $component->id,
                'file_id' => $log->id,
            ]))
            ->assertForbidden();

        $this->actingAsForApi($updater)
            ->get(route('api.files.show', [
                'object_type' => 'component-instances',
                'id' => $component->id,
                'file_id' => $log->id,
            ]))
            ->assertOk();

        $this->actingAsForApi($updater)
            ->delete(route('api.files.destroy', [
                'object_type' => 'component-instances',
                'id' => $component->id,
                'file_id' => $log->id,
            ]))
            ->assertOk()
            ->assertStatusMessageIs('success');

        $this->assertSoftDeleted('action_logs', ['id' => $log->id]);
    }

    public function testPortalViewerCanReadVisibleWorkOrderFilesButOnlyUpdaterCanMutateThem(): void
    {
        $company = Company::factory()->create();
        $workOrder = WorkOrder::factory()->for($company)->create();
        $updater = $this->userWithPermissions(['workorders.update'], $company);
        $portalViewer = $this->userWithPermissions(['portal.view'], $company);
        $log = $this->uploadAs($updater, 'work-orders', $workOrder);

        $this->actingAsForApi($portalViewer)
            ->getJson(route('api.files.index', [
                'object_type' => 'work-orders',
                'id' => $workOrder->id,
            ]))
            ->assertOk()
            ->assertJsonPath('total', 1);

        $this->actingAsForApi($portalViewer)
            ->get(route('api.files.show', [
                'object_type' => 'work-orders',
                'id' => $workOrder->id,
                'file_id' => $log->id,
            ]))
            ->assertOk();

        $this->actingAsForApi($portalViewer)
            ->post(route('api.files.store', [
                'object_type' => 'work-orders',
                'id' => $workOrder->id,
            ]), ['file' => [$this->attachment()]])
            ->assertForbidden();

        $this->actingAsForApi($portalViewer)
            ->delete(route('api.files.destroy', [
                'object_type' => 'work-orders',
                'id' => $workOrder->id,
                'file_id' => $log->id,
            ]))
            ->assertForbidden();

        $this->actingAsForApi($updater)
            ->getJson(route('api.files.index', [
                'object_type' => 'work-orders',
                'id' => $workOrder->id,
            ]))
            ->assertOk()
            ->assertJsonPath('rows.0.available_actions.delete', true);

        $this->actingAsForApi($updater)
            ->delete(route('api.files.destroy', [
                'object_type' => 'work-orders',
                'id' => $workOrder->id,
                'file_id' => $log->id,
            ]))
            ->assertOk()
            ->assertStatusMessageIs('success');
    }

    public function testLegacyDedicatedFilePermissionRemainsSufficientAndViewOrEditIsNot(): void
    {
        $cases = [
            ['accessories', Accessory::factory()->create(), 'accessories'],
            ['consumables', Consumable::factory()->create(), 'consumables'],
            ['licenses', License::factory()->create(), 'licenses'],
        ];

        foreach ($cases as [$objectType, $parent, $permissionPrefix]) {
            $fileManager = $this->userWithPermissions(["{$permissionPrefix}.files"]);
            $ordinaryEditor = $this->userWithPermissions([
                "{$permissionPrefix}.view",
                "{$permissionPrefix}.edit",
            ]);
            $log = $this->uploadAs($fileManager, $objectType, $parent);

            $this->actingAsForApi($ordinaryEditor)
                ->getJson(route('api.files.index', [
                    'object_type' => $objectType,
                    'id' => $parent->id,
                ]))
                ->assertForbidden();

            $this->actingAsForApi($ordinaryEditor)
                ->post(route('api.files.store', [
                    'object_type' => $objectType,
                    'id' => $parent->id,
                ]), ['file' => [$this->attachment()]])
                ->assertForbidden();

            $this->actingAsForApi($ordinaryEditor)
                ->delete(route('api.files.destroy', [
                    'object_type' => $objectType,
                    'id' => $parent->id,
                    'file_id' => $log->id,
                ]))
                ->assertForbidden();

            $this->actingAsForApi($fileManager)
                ->getJson(route('api.files.index', [
                    'object_type' => $objectType,
                    'id' => $parent->id,
                ]))
                ->assertOk()
                ->assertJsonPath('total', 1);

            $this->actingAsForApi($fileManager)
                ->get(route('api.files.show', [
                    'object_type' => $objectType,
                    'id' => $parent->id,
                    'file_id' => $log->id,
                ]))
                ->assertOk();

            $this->actingAsForApi($fileManager)
                ->delete(route('api.files.destroy', [
                    'object_type' => $objectType,
                    'id' => $parent->id,
                    'file_id' => $log->id,
                ]))
                ->assertOk()
                ->assertStatusMessageIs('success');
        }
    }

    public function testAttachmentIdsAreScopedToTheRequestedParentAndType(): void
    {
        $assetA = Asset::factory()->create();
        $assetB = Asset::factory()->create();
        $accessory = Accessory::factory()->create();
        $manager = $this->userWithPermissions([
            'assets.files.view',
            'assets.files.upload',
            'assets.files.manage',
            'accessories.files',
        ]);
        $log = $this->uploadAs($manager, 'assets', $assetA);

        $this->actingAsForApi($manager)
            ->get(route('api.files.show', [
                'object_type' => 'assets',
                'id' => $assetB->id,
                'file_id' => $log->id,
            ]))
            ->assertOk()
            ->assertStatusMessageIs('error');

        $this->actingAsForApi($manager)
            ->delete(route('api.files.destroy', [
                'object_type' => 'assets',
                'id' => $assetB->id,
                'file_id' => $log->id,
            ]))
            ->assertStatus(500)
            ->assertStatusMessageIs('error');

        $this->actingAsForApi($manager)
            ->get(route('api.files.show', [
                'object_type' => 'accessories',
                'id' => $accessory->id,
                'file_id' => $log->id,
            ]))
            ->assertOk()
            ->assertStatusMessageIs('error');

        $this->actingAsForApi($manager)
            ->delete(route('api.files.destroy', [
                'object_type' => 'accessories',
                'id' => $accessory->id,
                'file_id' => $log->id,
            ]))
            ->assertStatus(500)
            ->assertStatusMessageIs('error');

        $this->assertDatabaseHas('action_logs', ['id' => $log->id, 'deleted_at' => null]);
        Storage::disk(config('filesystems.default'))->assertExists($log->uploads_file_path());
    }

    public function testFullCompanyScopeHidesOtherCompanyParentsForEveryFileAction(): void
    {
        [$companyA, $companyB] = Company::factory()->count(2)->create();
        $asset = Asset::factory()->for($companyB)->create();
        $superuser = User::factory()->superuser()->create();
        $log = $this->uploadAs($superuser, 'assets', $asset);
        $scopedManager = $this->userWithPermissions([
            'assets.files.view',
            'assets.files.upload',
            'assets.files.manage',
        ], $companyA);

        $this->settings->enableMultipleFullCompanySupport();

        $this->actingAsForApi($scopedManager)
            ->getJson(route('api.files.index', ['object_type' => 'assets', 'id' => $asset->id]))
            ->assertOk()
            ->assertStatusMessageIs('error');

        $this->actingAsForApi($scopedManager)
            ->post(route('api.files.store', ['object_type' => 'assets', 'id' => $asset->id]), [
                'file' => [$this->attachment()],
            ])
            ->assertOk()
            ->assertStatusMessageIs('error');

        $this->actingAsForApi($scopedManager)
            ->get(route('api.files.show', [
                'object_type' => 'assets',
                'id' => $asset->id,
                'file_id' => $log->id,
            ]))
            ->assertOk()
            ->assertStatusMessageIs('error');

        $this->actingAsForApi($scopedManager)
            ->delete(route('api.files.destroy', [
                'object_type' => 'assets',
                'id' => $asset->id,
                'file_id' => $log->id,
            ]))
            ->assertOk()
            ->assertStatusMessageIs('error');

        $this->assertDatabaseHas('action_logs', ['id' => $log->id, 'deleted_at' => null]);
        Storage::disk(config('filesystems.default'))->assertExists($log->uploads_file_path());
    }

    public function testMaintenanceFilesAreReadOnlyWhileModelNumberFilesUseTheirOwningPolicy(): void
    {
        $asset = Asset::factory()->create();
        $maintenance = Maintenance::factory()->for($asset)->create();
        $model = AssetModel::factory()->create();
        $modelNumber = ModelNumber::factory()->for($model, 'model')->create();
        $assetViewer = $this->userWithPermissions(['assets.view']);
        $modelEditor = $this->userWithPermissions(['models.edit']);

        $this->actingAs($assetViewer);
        $maintenanceLog = $maintenance->logUpload('historical-maintenance.txt', 'Imported history');
        Storage::put($maintenanceLog->uploads_file_path(), 'historical maintenance attachment');

        $this->actingAsForApi($assetViewer)
            ->getJson(route('api.files.index', [
                'object_type' => 'maintenances',
                'id' => $maintenance->id,
            ]))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rows.0.available_actions.delete', false);

        $this->actingAsForApi($assetViewer)
            ->post('/api/v1/maintenances/'.$maintenance->id.'/files', [
                'file' => [$this->attachment()],
            ])
            ->assertStatus(405);

        $this->actingAsForApi($assetViewer)
            ->delete('/api/v1/maintenances/'.$maintenance->id.'/files/'.$maintenanceLog->id.'/delete')
            ->assertStatus(405);

        $this->assertDatabaseHas('action_logs', [
            'id' => $maintenanceLog->id,
            'deleted_at' => null,
        ]);
        Storage::assertExists($maintenanceLog->uploads_file_path());

        $modelNumberLog = $this->uploadAs($modelEditor, 'model-numbers', $modelNumber);

        $this->actingAsForApi($modelEditor)
            ->getJson(route('api.files.index', [
                'object_type' => 'model-numbers',
                'id' => $modelNumber->id,
            ]))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rows.0.url', route('ui.files.show', [
                'object_type' => 'model-numbers',
                'id' => $modelNumber->id,
                'file_id' => $modelNumberLog->id,
            ]));

        $this->actingAsForApi($modelEditor)
            ->delete(route('api.files.destroy', [
                'object_type' => 'model-numbers',
                'id' => $modelNumber->id,
                'file_id' => $modelNumberLog->id,
            ]))
            ->assertOk()
            ->assertStatusMessageIs('success');
    }

    private function uploadAs(User $user, string $objectType, Model $parent): Actionlog
    {
        $this->actingAsForApi($user)
            ->post(route('api.files.store', [
                'object_type' => $objectType,
                'id' => $parent->getKey(),
            ]), [
                'file' => [$this->attachment()],
            ])
            ->assertOk()
            ->assertStatusMessageIs('success');

        return Actionlog::withoutGlobalScopes()
            ->where('action_type', 'uploaded')
            ->where('item_type', $parent::class)
            ->where('item_id', $parent->getKey())
            ->latest('id')
            ->firstOrFail();
    }

    private function attachment(): UploadedFile
    {
        return UploadedFile::fake()->create('attachment.txt', 1, 'text/plain');
    }

    private function userWithPermissions(array $permissions, ?Company $company = null): User
    {
        $factory = User::factory();

        if ($company) {
            $factory = $factory->for($company);
        }

        return $factory->create([
            'permissions' => json_encode(array_fill_keys($permissions, '1')),
        ]);
    }
}
