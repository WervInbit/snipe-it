<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\ComponentDefinition;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use App\Models\Location;
use App\Models\ModelNumber;
use App\Models\ModelNumberComponentTemplate;
use App\Models\Statuslabel;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ComponentLifecycleService;
use App\Services\Components\AssetExpectedComponentService;
use App\Services\Components\ComponentExpectedSubcomponentService;
use Carbon\CarbonImmutable;
use Database\Seeders\Concerns\GuardsDisposableDataSeeding;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DevelopmentDeviceScenarioSeeder extends Seeder
{
    use GuardsDisposableDataSeeding;

    private const ASSET_TAG_PREFIX = 'DEV-COMP-';
    private const DEV_ACTOR_USERNAME = 'dev_component_seed_admin';
    private const DEV_SUPPLIER_NAME = 'DEV - Component Scenario Supplier';

    private User $actor;

    public function __construct(
        private readonly ComponentLifecycleService $lifecycle,
        private readonly AssetExpectedComponentService $expectedComponents,
        private readonly ComponentExpectedSubcomponentService $expectedSubcomponents,
    ) {
    }

    public function run(): void
    {
        $this->assertDisposableDataSeedingAllowed();

        if (!Schema::hasTable('component_instances')) {
            return;
        }

        $this->call(ProductionFoundationSeeder::class);

        DB::transaction(function (): void {
            $this->actor = $this->resolveActor();
            $locations = $this->ensureAssetLocations();
            $storageLocations = $this->ensureComponentStorageLocations();
            $supplier = $this->ensureSupplier();

            $this->cleanupExistingScenarioRows();

            $this->createBaselineLaptopScenario($locations, $supplier);
            $this->createComplexLaptopScenario($locations, $storageLocations, $supplier);
            $this->createPhoneScenario($locations, $storageLocations, $supplier);
            $this->createTabletScenario($locations, $storageLocations, $supplier);
            $this->createLooseComponentScenarios($storageLocations, $supplier);
        });
    }

    /**
     * @param Collection<string,Location> $locations
     */
    private function createBaselineLaptopScenario(Collection $locations, Supplier $supplier): Asset
    {
        return $this->createAsset(
            tag: 'DEV-COMP-001',
            modelNumberCode: '2E9F8EA#ABH',
            name: 'DEV Baseline HP ProBook 450 G8',
            serial: 'DEV-HP450G8-BASELINE',
            statusName: 'Being Processed',
            location: $locations->get('bench'),
            supplier: $supplier,
            notes: 'Development scenario: model-number expected components only, no tracked component instances.'
        );
    }

    /**
     * @param Collection<string,Location> $locations
     * @param Collection<string,ComponentStorageLocation> $storageLocations
     */
    private function createComplexLaptopScenario(Collection $locations, Collection $storageLocations, Supplier $supplier): Asset
    {
        $asset = $this->createAsset(
            tag: 'DEV-COMP-002',
            modelNumberCode: '2E9F8EA#ABH',
            name: 'DEV Complex HP ProBook 450 G8',
            serial: 'DEV-HP450G8-COMPLEX',
            statusName: 'Being Processed',
            location: $locations->get('bench'),
            supplier: $supplier,
            notes: 'Development scenario: tracked expected board, child components, removed expected component, extra part, and custom part.'
        );

        $board = $this->materializeExpectedComponent($asset, 'Motherboard - HP ProBook 450 G8 - i5-1135G7', 'complex-laptop-board');
        $board = $this->lifecycle->confirmVerification($board, null, [
            'performed_by' => $this->actor,
            'note' => 'DEV scenario: motherboard verified as good.',
        ]);
        $this->markDevComponent($board, 'complex-laptop-board');

        $damagedUsbC = $this->materializeExpectedSubcomponent($board, 'USB-C Port - USB 3.1 Gen2 - DP 1.4 Alt - PD', 'complex-laptop-damaged-child');
        $damagedUsbC = $this->lifecycle->updateCondition($damagedUsbC, ComponentInstance::CONDITION_BROKEN, [
            'performed_by' => $this->actor,
            'note' => 'DEV scenario: USB-C port is physically damaged.',
        ]);
        $this->markDevComponent($damagedUsbC, 'complex-laptop-damaged-child');

        $removedHdmi = $this->materializeExpectedSubcomponent($board, 'HDMI Port - 1.4b', 'complex-laptop-removed-child');
        $removedHdmi = $this->lifecycle->moveToStock($removedHdmi, $storageLocations->get('verification'), [
            'performed_by' => $this->actor,
            'needs_verification' => true,
            'storage_location' => $storageLocations->get('verification'),
            'note' => 'DEV scenario: HDMI child component moved off the board for verification.',
        ]);
        $this->markDevComponent($removedHdmi, 'complex-laptop-removed-child');

        $extraSsd = $this->createLooseComponent(
            definitionName: 'SSD - Generic',
            displayName: 'DEV Extra SSD - 512GB',
            serial: 'DEV-SSD-512',
            conditionCode: ComponentInstance::CONDITION_GOOD,
            storageLocation: $storageLocations->get('stock'),
            supplier: $supplier,
            scenario: 'complex-laptop-extra-ssd'
        );
        $extraSsd = $this->lifecycle->installIntoAsset($extraSsd, $asset, [
            'performed_by' => $this->actor,
            'installed_as' => 'Secondary storage',
            'note' => 'DEV scenario: extra SSD installed beyond the model baseline.',
        ]);
        $this->markDevComponent($extraSsd, 'complex-laptop-extra-ssd');

        $customPart = $this->createLooseComponent(
            definitionName: null,
            displayName: 'DEV Custom Unknown Daughterboard',
            serial: 'DEV-CUSTOM-DAUGHTERBOARD',
            conditionCode: ComponentInstance::CONDITION_POOR,
            storageLocation: $storageLocations->get('stock'),
            supplier: $supplier,
            scenario: 'complex-laptop-custom-part'
        );
        $customPart = $this->lifecycle->installIntoAsset($customPart, $asset, [
            'performed_by' => $this->actor,
            'condition_warning_confirmed' => true,
            'installed_as' => 'Unknown internal board',
            'note' => 'DEV scenario: custom vague part installed with poor condition warning confirmed.',
        ]);
        $this->markDevComponent($customPart, 'complex-laptop-custom-part');

        return $asset;
    }

    /**
     * @param Collection<string,Location> $locations
     * @param Collection<string,ComponentStorageLocation> $storageLocations
     */
    private function createPhoneScenario(Collection $locations, Collection $storageLocations, Supplier $supplier): Asset
    {
        $asset = $this->createAsset(
            tag: 'DEV-COMP-003',
            modelNumberCode: 'PIXEL8PRO-256-OBSIDIAN',
            name: 'DEV Pixel 8 Pro Camera Scenario',
            serial: 'DEV-PIXEL8PRO-CAMERA',
            statusName: 'Being Processed',
            location: $locations->get('intake'),
            supplier: $supplier,
            notes: 'Development scenario: phone with removed expected camera and a generic tracked camera substitute.'
        );

        $removedMainCamera = $this->materializeExpectedComponent($asset, 'Camera - Main - 50MP', 'phone-removed-main-camera');
        $removedMainCamera = $this->lifecycle->removeToTray($removedMainCamera, $this->actor, [
            'note' => 'DEV scenario: expected main camera removed to tray.',
        ]);
        $this->markDevComponent($removedMainCamera, 'phone-removed-main-camera');

        $genericCamera = $this->createLooseComponent(
            definitionName: 'Camera - Generic',
            displayName: 'DEV Generic Replacement Camera',
            serial: 'DEV-CAMERA-GENERIC',
            conditionCode: ComponentInstance::CONDITION_UNKNOWN,
            storageLocation: $storageLocations->get('stock'),
            supplier: $supplier,
            scenario: 'phone-generic-camera'
        );
        $genericCamera = $this->lifecycle->installIntoAsset($genericCamera, $asset, [
            'performed_by' => $this->actor,
            'condition_warning_confirmed' => true,
            'installed_as' => 'Temporary camera',
            'note' => 'DEV scenario: vague camera installed to test generic component readability.',
        ]);
        $this->markDevComponent($genericCamera, 'phone-generic-camera');

        return $asset;
    }

    /**
     * @param Collection<string,Location> $locations
     * @param Collection<string,ComponentStorageLocation> $storageLocations
     */
    private function createTabletScenario(Collection $locations, Collection $storageLocations, Supplier $supplier): Asset
    {
        $asset = $this->createAsset(
            tag: 'DEV-COMP-004',
            modelNumberCode: 'MS-SURFPRO4-I5-4-128',
            name: 'DEV Surface Pro 4 Edge Case',
            serial: 'DEV-SURFPRO4-EDGE',
            statusName: 'QA Hold',
            location: $locations->get('qa'),
            supplier: $supplier,
            notes: 'Development scenario: tablet-style laptop with integrated board children and removed battery.'
        );

        $removedBattery = $this->expectedComponents->materializeToStock(
            $asset,
            $this->templateFor($asset, 'Battery 38 Wh'),
            $storageLocations->get('verification'),
            $this->actor,
            [
                'needs_verification' => true,
                'verification_location' => $storageLocations->get('verification'),
                'note' => 'DEV scenario: Surface battery moved to verification stock.',
            ]
        );
        $this->markDevComponent($removedBattery, 'tablet-removed-battery');

        return $asset;
    }

    /**
     * @param Collection<string,ComponentStorageLocation> $storageLocations
     */
    private function createLooseComponentScenarios(Collection $storageLocations, Supplier $supplier): void
    {
        $this->createLooseComponent(
            definitionName: 'SSD - Generic',
            displayName: 'DEV Loose Stock SSD',
            serial: 'DEV-LOOSE-SSD',
            conditionCode: ComponentInstance::CONDITION_GOOD,
            storageLocation: $storageLocations->get('stock'),
            supplier: $supplier,
            scenario: 'loose-stock-ssd'
        );

        $this->createTrayComponent(
            definitionName: 'RAM - Generic',
            displayName: 'DEV Loose Tray RAM',
            serial: 'DEV-LOOSE-RAM',
            supplier: $supplier,
            scenario: 'loose-tray-ram'
        );

        $battery = $this->createLooseComponent(
            definitionName: 'Battery - Generic',
            displayName: 'DEV Loose Verification Battery',
            serial: 'DEV-LOOSE-BATTERY',
            conditionCode: ComponentInstance::CONDITION_UNKNOWN,
            storageLocation: $storageLocations->get('verification'),
            supplier: $supplier,
            scenario: 'loose-verification-battery'
        );
        $battery = $this->lifecycle->flagNeedsVerification($battery, [
            'performed_by' => $this->actor,
            'storage_location' => $storageLocations->get('verification'),
            'note' => 'DEV scenario: loose battery needs verification.',
        ]);
        $this->markDevComponent($battery, 'loose-verification-battery');

        $bluetooth = $this->createLooseComponent(
            definitionName: 'Bluetooth - Generic',
            displayName: 'DEV Loose Broken Bluetooth',
            serial: 'DEV-LOOSE-BLUETOOTH',
            conditionCode: ComponentInstance::CONDITION_BROKEN,
            storageLocation: $storageLocations->get('stock'),
            supplier: $supplier,
            scenario: 'loose-broken-bluetooth'
        );
        $this->markDevComponent($bluetooth, 'loose-broken-bluetooth');
    }

    private function materializeExpectedComponent(Asset $asset, string $definitionName, string $scenario): ComponentInstance
    {
        $component = $this->expectedComponents->materializeExpectedBaseline(
            $asset,
            $this->templateFor($asset, $definitionName),
            $this->actor,
            [
                'note' => "DEV scenario: materialized expected component {$definitionName}.",
            ]
        );

        return $this->markDevComponent($component, $scenario);
    }

    private function materializeExpectedSubcomponent(ComponentInstance $parent, string $definitionName, string $scenario): ComponentInstance
    {
        $parent->loadMissing('componentDefinition.subcomponentTemplates.childComponentDefinition');

        $template = $parent->componentDefinition
            ->subcomponentTemplates
            ->first(function ($template) use ($definitionName): bool {
                return $template->childComponentDefinition?->name === $definitionName;
            });

        if (!$template) {
            throw new \RuntimeException("Missing expected subcomponent template for {$definitionName}.");
        }

        $component = $this->expectedSubcomponents->materializeAttachedChild($parent, $template, $this->actor, [
            'condition_warning_confirmed' => true,
            'note' => "DEV scenario: materialized expected subcomponent {$definitionName}.",
        ]);

        return $this->markDevComponent($component, $scenario);
    }

    private function createAsset(
        string $tag,
        string $modelNumberCode,
        string $name,
        string $serial,
        string $statusName,
        Location $location,
        Supplier $supplier,
        string $notes,
    ): Asset {
        $modelNumber = ModelNumber::with('model')
            ->where('code', $modelNumberCode)
            ->firstOrFail();
        $status = Statuslabel::query()
            ->where('name', $statusName)
            ->firstOrFail();

        return Asset::factory()->create([
            'asset_tag' => $tag,
            'name' => $name,
            'serial' => $serial,
            'model_id' => $modelNumber->model_id,
            'model_number_id' => $modelNumber->id,
            'status_id' => $status->id,
            'quality_grade' => Asset::QUALITY_GRADE_B,
            'rtd_location_id' => $location->id,
            'location_id' => $location->id,
            'supplier_id' => $supplier->id,
            'created_by' => $this->actor->id,
            'notes' => $notes,
            'purchase_date' => CarbonImmutable::parse('2026-06-01')->toDateString(),
            'purchase_cost' => 0,
            'requestable' => 0,
            'is_sellable' => 0,
        ]);
    }

    private function createLooseComponent(
        ?string $definitionName,
        string $displayName,
        string $serial,
        string $conditionCode,
        ComponentStorageLocation $storageLocation,
        Supplier $supplier,
        string $scenario,
    ): ComponentInstance {
        $component = $this->lifecycle->createInstance([
            'component_definition_id' => $definitionName ? $this->definition($definitionName)->id : null,
            'display_name' => $displayName,
            'serial' => $serial,
            'status' => ComponentInstance::STATUS_IN_STOCK,
            'lifecycle_status' => ComponentInstance::LIFECYCLE_IN_STOCK,
            'condition_code' => $conditionCode,
            'source_type' => ComponentInstance::SOURCE_EXTERNAL_INTAKE,
            'storage_location_id' => $storageLocation->id,
            'supplier_id' => $supplier->id,
            'received_at' => CarbonImmutable::parse('2026-06-01'),
            'notes' => "Development device scenario seed: {$scenario}.",
            'metadata_json' => $this->devMetadata($scenario),
        ], $this->actor);

        return $this->markDevComponent($component, $scenario);
    }

    private function createTrayComponent(
        string $definitionName,
        string $displayName,
        string $serial,
        Supplier $supplier,
        string $scenario,
    ): ComponentInstance {
        $component = $this->lifecycle->createInstance([
            'component_definition_id' => $this->definition($definitionName)->id,
            'display_name' => $displayName,
            'serial' => $serial,
            'status' => ComponentInstance::STATUS_IN_TRANSFER,
            'lifecycle_status' => ComponentInstance::LIFECYCLE_IN_TRAY,
            'condition_code' => ComponentInstance::CONDITION_UNKNOWN,
            'source_type' => ComponentInstance::SOURCE_EXTERNAL_INTAKE,
            'held_by_user_id' => $this->actor->id,
            'transfer_started_at' => CarbonImmutable::parse('2026-06-01 09:00:00'),
            'supplier_id' => $supplier->id,
            'received_at' => CarbonImmutable::parse('2026-06-01'),
            'notes' => "Development device scenario seed: {$scenario}.",
            'metadata_json' => $this->devMetadata($scenario),
        ], $this->actor);

        return $this->markDevComponent($component, $scenario);
    }

    private function templateFor(Asset $asset, string $definitionName): ModelNumberComponentTemplate
    {
        return ModelNumberComponentTemplate::query()
            ->where('model_number_id', $asset->model_number_id)
            ->whereHas('componentDefinition', function ($query) use ($definitionName): void {
                $query->where('name', $definitionName);
            })
            ->firstOrFail();
    }

    private function definition(string $name): ComponentDefinition
    {
        return ComponentDefinition::query()
            ->where('name', $name)
            ->firstOrFail();
    }

    private function markDevComponent(ComponentInstance $component, string $scenario): ComponentInstance
    {
        $metadata = $component->metadata_json ?? [];
        $component->forceFill([
            'metadata_json' => array_merge($metadata, $this->devMetadata($scenario)),
        ])->save();

        return $component->fresh();
    }

    /**
     * @return array<string,mixed>
     */
    private function devMetadata(string $scenario): array
    {
        return [
            'dev_seed' => true,
            'dev_scenario' => $scenario,
            'seeded_by' => self::class,
        ];
    }

    private function cleanupExistingScenarioRows(): void
    {
        $assetIds = Asset::withTrashed()
            ->where('asset_tag', 'like', self::ASSET_TAG_PREFIX . '%')
            ->pluck('id');

        $componentIds = ComponentInstance::withTrashed()
            ->where(function ($query) use ($assetIds): void {
                $query
                    ->where('metadata_json', 'like', '%dev_seed%')
                    ->orWhere('display_name', 'like', 'DEV %');

                if ($assetIds->isNotEmpty()) {
                    $query
                        ->orWhereIn('source_asset_id', $assetIds)
                        ->orWhereIn('current_asset_id', $assetIds)
                        ->orWhereIn('root_asset_id', $assetIds);
                }
            })
            ->pluck('id');

        if ($componentIds->isNotEmpty()) {
            ComponentInstance::withTrashed()
                ->whereIn('id', $componentIds)
                ->orWhereIn('parent_component_instance_id', $componentIds)
                ->get()
                ->sortByDesc(fn (ComponentInstance $component) => $component->parent_component_instance_id ? 1 : 0)
                ->each(function (ComponentInstance $component): void {
                    $component->forceDelete();
                });
        }

        if ($assetIds->isNotEmpty()) {
            Asset::withTrashed()
                ->whereIn('id', $assetIds)
                ->get()
                ->each(function (Asset $asset): void {
                    $asset->forceDelete();
                });
        }
    }

    private function resolveActor(): User
    {
        $existingSuperuser = User::query()
            ->where('permissions->superuser', '1')
            ->orWhere('permissions->superuser', 1)
            ->first();

        if ($existingSuperuser) {
            return $existingSuperuser;
        }

        $user = User::withTrashed()
            ->where('username', self::DEV_ACTOR_USERNAME)
            ->first() ?? new User(['username' => self::DEV_ACTOR_USERNAME]);

        if ($user->trashed()) {
            $user->restore();
        }

        $user->fill([
            'first_name' => 'Dev',
            'last_name' => 'Component Seed',
            'email' => 'dev.component.seed@example.test',
            'password' => Hash::make('password'),
            'activated' => 1,
            'locale' => 'nl-NL',
            'notes' => 'Created by DevelopmentDeviceScenarioSeeder when no superuser exists.',
        ]);
        $user->permissions = json_encode(['superuser' => 1]);
        $user->save();

        return $user;
    }

    /**
     * @return Collection<string,Location>
     */
    private function ensureAssetLocations(): Collection
    {
        $locations = [
            'intake' => 'DEV - Intake',
            'bench' => 'DEV - Processing Bench',
            'qa' => 'DEV - QA Hold',
            'ready' => 'DEV - Ready Shelf',
        ];

        return collect($locations)
            ->mapWithKeys(function (string $name, string $key): array {
                /** @var Location $location */
                $location = Location::firstOrCreate(
                    ['name' => $name],
                    [
                        'city' => 'Development',
                        'country' => 'NL',
                        'notes' => 'Created by DevelopmentDeviceScenarioSeeder.',
                    ]
                );

                return [$key => $location];
            });
    }

    /**
     * @return Collection<string,ComponentStorageLocation>
     */
    private function ensureComponentStorageLocations(): Collection
    {
        foreach (config('components.default_storage_locations', []) as $location) {
            ComponentStorageLocation::firstOrCreate(
                ['code' => $location['code']],
                [
                    'name' => $location['name'],
                    'type' => $location['type'],
                    'is_active' => true,
                ]
            );
        }

        return ComponentStorageLocation::query()
            ->whereIn('code', ['stock', 'tray', 'verification', 'destruction'])
            ->get()
            ->keyBy('code');
    }

    private function ensureSupplier(): Supplier
    {
        /** @var Supplier $supplier */
        $supplier = Supplier::withTrashed()
            ->where('name', self::DEV_SUPPLIER_NAME)
            ->first() ?? new Supplier(['name' => self::DEV_SUPPLIER_NAME]);

        if ($supplier->trashed()) {
            $supplier->restore();
        }

        $supplier->fill([
            'notes' => 'Development-only supplier for component scenario assets.',
        ]);
        $supplier->save();

        return $supplier;
    }
}
