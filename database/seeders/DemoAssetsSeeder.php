<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\ModelNumberAttribute;
use App\Models\Category;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\Setting;
use App\Models\Statuslabel;
use App\Models\Supplier;
use App\Models\TestResult;
use App\Models\TestRun;
use App\Models\User;
use App\Models\WorkflowProfile;
use App\Services\ModelAttributes\AttributeValueService;
use App\Services\QrLabelService;
use App\Services\WorkflowRunDefinitionService;
use Carbon\CarbonImmutable;
use Database\Seeders\Concerns\GuardsDisposableDataSeeding;
use Database\Seeders\Concerns\ProvidesDeviceCatalogData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DemoAssetsSeeder extends Seeder
{
    use GuardsDisposableDataSeeding;
    use ProvidesDeviceCatalogData;

    public function run(): void
    {
        $this->assertDisposableDataSeedingAllowed();
        $this->resetTables();

        $admin = User::where('permissions->superuser', '1')->first();

        if ($admin) {
            Auth::login($admin);
        }

        try {
            $models = $this->seedModelBlueprints();
            $seededAssets = $this->seedAssets($models);
            $this->seedTestRuns($seededAssets['assets'], $seededAssets['sale_status_ids']);
            $this->bumpUiStateVersion();
        } finally {
            if ($admin) {
                Auth::logout();
            }
        }
    }

    /**
     * Remove existing asset/test data so the curated dataset stays small.
     *
     * Keep foreign keys enabled and do not truncate assets. Runtime component
     * hierarchies are part of the disposable dataset and are removed before
     * assets; work-order links keep their snapshots with a null asset reference.
     * Preserving the asset auto-increment prevents unkeyed legacy rows from
     * becoming associated with newly created demo assets by ID reuse.
     */
    private function resetTables(): void
    {
        DB::table('work_order_assets')->update(['asset_id' => null]);

        foreach ([
            'component_events',
            'component_instances',
            'workflow_result_photos',
            'workflow_results',
            'workflow_runs',
            'workflow_audits',
            'asset_tests',
            'asset_logs',
            'asset_images',
            'asset_status_history',
            'checkout_requests',
            'assets',
        ] as $table) {
            DB::table($table)->delete();
        }
    }

    /**
     * Seed a limited slice of the model catalog using the shared blueprints.
     *
     * @return array<string,array{model:\App\Models\AssetModel,model_number_id:int}>
     */
    private function seedModelBlueprints(): array
    {
        $valueService = app(AttributeValueService::class);
        $blueprints = collect($this->modelBlueprints())->only(
            array_merge($this->demoModelKeys(), $this->expansionModelKeys())
        );

        $attributeKeys = $blueprints
            ->flatMap(fn ($config) => array_keys($config['attributes']))
            ->unique()
            ->values();

        /** @var Collection<string,AttributeDefinition> $definitions */
        $definitions = AttributeDefinition::whereIn('key', $attributeKeys)
            ->get()
            ->keyBy('key');

        $catalog = [];

        foreach ($blueprints as $name => $config) {
            $model = AssetModel::where('name', $name)->first();

            if (! $model && isset($config['factory']) && is_callable($config['factory'])) {
                $model = $config['factory']();
            }

            if (! $model) {
                $categoryName = str_contains(strtolower($name), 'pixel') ? 'Mobile Phones' : 'Laptops';
                $manufacturerName = $config['attributes']['brand'] ?? 'Unknown';

                $categoryId = Category::where('name', $categoryName)->value('id');
                $manufacturerId = Manufacturer::where('name', $manufacturerName)->value('id');

                $model = AssetModel::create([
                    'name' => $name,
                    'category_id' => $categoryId,
                    'manufacturer_id' => $manufacturerId,
                    'model_number' => $config['code'] ?? null,
                ]);
            }

            $modelNumber = $model->primaryModelNumber ?: $model->ensurePrimaryModelNumber();

            if (! empty($config['code'])) {
                $modelNumber->code = $config['code'];
            }

            if (! empty($config['label'])) {
                $modelNumber->label = $config['label'];
            }

            $modelNumber->save();

            if ($model->primary_model_number_id !== $modelNumber->id) {
                $model->forceFill([
                    'primary_model_number_id' => $modelNumber->id,
                    'model_number' => $modelNumber->code,
                ])->save();
            }

            $position = 0;
            $assigned = [];

            foreach ($config['attributes'] as $key => $value) {
                $definition = $definitions->get($key);

                if (! $definition) {
                    continue;
                }

                try {
                    $tuple = $valueService->validateAndNormalize($definition, $value);
                } catch (\Throwable) {
                    continue;
                }

                ModelNumberAttribute::updateOrCreate(
                    [
                        'model_number_id' => $modelNumber->id,
                        'attribute_definition_id' => $definition->id,
                    ],
                    [
                        'value' => $tuple->value,
                        'raw_value' => $tuple->rawValue,
                        'attribute_option_id' => $tuple->attributeOptionId,
                        'display_order' => $position++,
                    ]
                );

                $assigned[] = $definition->id;
            }

            if (! empty($assigned)) {
                ModelNumberAttribute::query()
                    ->where('model_number_id', $modelNumber->id)
                    ->whereNotIn('attribute_definition_id', $assigned)
                    ->delete();
            }

            $catalog[$name] = [
                'model' => $model,
                'model_number_id' => $modelNumber->id,
            ];
        }

        return $catalog;
    }

    /**
     * Create a curated asset list tied to the seeded models.
     *
     * @param array<string,array{model:\App\Models\AssetModel,model_number_id:int}> $models
     * Sale-lifecycle assets are created in a safe processing state. Their
     * intended statuses are applied only after complete readiness runs exist.
     *
     * @return array{assets: array<int,\App\Models\Asset>, sale_status_ids: array<int,int>}
     */
    private function seedAssets(array $models): array
    {
        $status = Statuslabel::query()->get()->keyBy('name');
        $processingStatus = $status->get('Being Processed');
        $locations = Location::query()->pluck('id', 'name');
        $suppliers = Supplier::query()->pluck('id', 'name');
        $users = User::query()->pluck('id', 'username');

        $qr = app(QrLabelService::class);

        $assets = [];
        $saleStatusIds = [];
        $records = [
            [
                'tag' => 'DEMO-001',
                'name' => 'HP ProBook 450 G8',
                'model_key' => 'HP ProBook 450 G8',
                'status' => 'Ready for Sale',
                'location' => 'Ready to Ship',
                'notes' => 'All refurb checks cleared; staged for sales.',
                'supplier' => $suppliers->first(),
            ],
            [
                'tag' => 'DEMO-002',
                'name' => 'HP ProBook 430 G7',
                'model_key' => 'HP ProBook 430 G7',
                'status' => 'Being Processed',
                'location' => 'Repair Bench',
                'notes' => 'Battery cycle validation pending before QA hand-off.',
                'supplier' => $suppliers->first(),
            ],
            [
                'tag' => 'DEMO-003',
                'name' => 'Samsung Galaxy A5',
                'model_key' => 'Samsung Galaxy A5',
                'status' => 'Stand-by',
                'location' => 'Refurb Intake',
                'notes' => 'Awaiting replacement battery calibration and cosmetic check.',
                'supplier' => $suppliers->get('Renewed Supply Co.') ?? $suppliers->first(),
            ],
            [
                'tag' => 'DEMO-004',
                'name' => 'Pixel 8 Pro',
                'model_key' => 'Pixel 8 Pro',
                'status' => 'Ready for Sale',
                'location' => 'Ready to Ship',
                'notes' => 'Flagship Android phone prepped for ecommerce batch.',
                'supplier' => $suppliers->get('Renewed Supply Co.') ?? $suppliers->first(),
            ],
            [
                'tag' => 'DEMO-005',
                'name' => 'HP ProBook 450 G7',
                'model_key' => 'HP ProBook 450 G7',
                'status' => 'QA Hold',
                'location' => 'QA Station',
                'notes' => 'Minor hinge play under review before final release.',
                'supplier' => $suppliers->first(),
            ],
            [
                'tag' => 'DEMO-006',
                'name' => 'HP ProBook 450 G6',
                'model_key' => 'HP ProBook 450 G6',
                'status' => 'Broken / Parts',
                'location' => 'Repair Bench',
                'notes' => 'Motherboard intermittently fails POST; retained for parts.',
                'supplier' => $suppliers->first(),
            ],
            [
                'tag' => 'DEMO-007',
                'name' => 'HP ProBook 430 G6',
                'model_key' => 'HP ProBook 430 G6',
                'status' => 'Internal Use',
                'location' => 'Office',
                'notes' => 'Allocated to internal bench for intake tooling.',
                'supplier' => $suppliers->first(),
            ],
            [
                'tag' => 'DEMO-008',
                'name' => 'HP ProBook 430 G3',
                'model_key' => 'HP ProBook 430 G3',
                'status' => 'Archived',
                'location' => 'Archive Storage',
                'notes' => 'Legacy unit retained for historical tracking.',
                'supplier' => $suppliers->first(),
            ],
            [
                'tag' => 'DEMO-009',
                'name' => 'Microsoft Surface Pro 4',
                'model_key' => 'Microsoft Surface Pro 4',
                'status' => 'Returned / RMA',
                'location' => 'Refurb Intake',
                'notes' => 'Returned after touch flicker report; pending reassessment.',
                'supplier' => $suppliers->get('Renewed Supply Co.') ?? $suppliers->first(),
            ],
            [
                'tag' => 'DEMO-010',
                'name' => 'Microsoft Surface Pro 5',
                'model_key' => 'Microsoft Surface Pro 5',
                'status' => 'Sold',
                'location' => 'Ready to Ship',
                'notes' => 'Closed sale in latest ecommerce batch.',
                'supplier' => $suppliers->get('Renewed Supply Co.') ?? $suppliers->first(),
            ],
        ];

        foreach ($records as $record) {
            $catalog = $models[$record['model_key']] ?? null;

            if (! $catalog) {
                continue;
            }

            $statusName = $record['status'];
            $statusLabel = $status->get($statusName);

            if (! $statusLabel) {
                throw new \RuntimeException("Missing required demo status [{$statusName}].");
            }

            $requiresReadiness = Asset::statusRequiresTestAck($statusLabel);

            if ($requiresReadiness && ! $processingStatus) {
                throw new \RuntimeException('Missing required demo staging status [Being Processed].');
            }

            $asset = Asset::factory()->create([
                'asset_tag' => $record['tag'],
                'name' => $record['name'],
                'notes' => $record['notes'],
                'model_id' => $catalog['model']->id,
                'model_number_id' => $catalog['model_number_id'],
                'status_id' => $requiresReadiness ? $processingStatus->id : $statusLabel->id,
                'tests_completed_ok' => false,
                'rtd_location_id' => $locations->get($record['location']),
                'supplier_id' => $record['supplier'],
                'purchase_date' => CarbonImmutable::now()->subMonths(2)->format('Y-m-d'),
                'purchase_cost' => 0,
                'assigned_to' => null,
                'assigned_type' => null,
                'accepted' => null,
                'expected_checkin' => null,
                'last_checkin' => null,
                'last_checkout' => null,
                'last_audit_date' => null,
                'next_audit_date' => null,
                'requestable' => false,
                'created_by' => $users->get('admin'),
            ]);

            if ($requiresReadiness) {
                $saleStatusIds[$asset->id] = $statusLabel->id;
            }

            try {
                $qr->generate($asset);
            } catch (\Throwable) {
                // Best-effort QR generation; ignore failures in demo data.
            }

            $assets[] = $asset;
        }

        return [
            'assets' => $assets,
            'sale_status_ids' => $saleStatusIds,
        ];
    }

    /**
     * Attach concise test history to the demo assets.
     *
     * @param array<int,\App\Models\Asset> $assets
     * @param array<int,int> $saleStatusIds Asset IDs keyed to their intended sale-lifecycle status IDs.
     */
    private function seedTestRuns(array $assets, array $saleStatusIds): void
    {
        $qaUser = User::where('username', 'qa_manager')->first();
        $standardProfile = WorkflowProfile::query()
            ->where('slug', 'standard-diagnostics')
            ->with('items')
            ->first();

        $fixtures = [
            'DEMO-001' => [
                'battery' => ['status' => TestResult::STATUS_PASS, 'note' => 'Wear level at 7%; passes HP battery diagnostics.'],
                'cpu' => ['status' => TestResult::STATUS_PASS, 'note' => 'Intel Core i5 stress test completed without throttling.'],
                'keyboard' => ['status' => TestResult::STATUS_PASS, 'note' => 'All keys responsive after deep clean.'],
                'ethernet' => ['status' => TestResult::STATUS_PASS, 'note' => '1 Gbps link negotiated via RJ-45 port.'],
                'storage' => ['status' => TestResult::STATUS_PASS, 'note' => 'NVMe SMART reports 99% health.'],
            ],
            'DEMO-002' => [
                'battery' => ['status' => TestResult::STATUS_FAIL, 'note' => 'Capacity at 72%; replacement pack queued.'],
                'display' => ['status' => TestResult::STATUS_PASS, 'note' => '13" panel calibrated with no stuck pixels.'],
                'keyboard' => ['status' => TestResult::STATUS_PASS, 'note' => 'Keycaps replaced on worn home-row.'],
                'wifi' => ['status' => TestResult::STATUS_PASS, 'note' => 'Connected to refurb AP at 866 Mbps.'],
            ],
            'DEMO-003' => [
                'battery' => ['status' => TestResult::STATUS_NVT, 'note' => 'Awaiting post-replacement discharge cycle.'],
                'display' => ['status' => TestResult::STATUS_PASS, 'note' => 'Super AMOLED panel inspected for burn-in.'],
                'front_camera' => ['status' => TestResult::STATUS_PASS, 'note' => 'Selfie camera autofocus verified.'],
                'rear_camera' => ['status' => TestResult::STATUS_PASS, 'note' => 'Primary camera optics cleaned and tested.'],
                'speaker' => ['status' => TestResult::STATUS_PASS, 'note' => 'Stereo speakers balanced after diagnostics.'],
            ],
            'DEMO-004' => [
                'display' => ['status' => TestResult::STATUS_PASS, 'note' => 'LTPO panel validated at 120Hz peak.'],
                'battery' => ['status' => TestResult::STATUS_PASS, 'note' => 'Full charge/discharge cycle completed successfully.'],
                'front_camera' => ['status' => TestResult::STATUS_PASS, 'note' => 'Selfie camera HDR sample reviewed.'],
                'rear_camera' => ['status' => TestResult::STATUS_PASS, 'note' => 'Triple-camera suite tested in QA harness.'],
                'wifi' => ['status' => TestResult::STATUS_PASS, 'note' => 'Wi-Fi 6E connectivity verified in lab.'],
            ],
            'DEMO-005' => [
                'display' => ['status' => TestResult::STATUS_PASS, 'note' => 'Panel uniformity within tolerance.'],
                'keyboard' => ['status' => TestResult::STATUS_FAIL, 'note' => 'Backspace key requires higher actuation force.'],
                'storage' => ['status' => TestResult::STATUS_PASS, 'note' => 'SSD read/write benchmark passed.'],
            ],
            'DEMO-006' => [
                'cpu' => ['status' => TestResult::STATUS_FAIL, 'note' => 'Thermal throttling under sustained load.'],
                'ram' => ['status' => TestResult::STATUS_PASS, 'note' => 'Memory diagnostics complete.'],
                'storage' => ['status' => TestResult::STATUS_NVT, 'note' => 'Storage test skipped due to board instability.'],
            ],
            'DEMO-007' => [
                'battery' => ['status' => TestResult::STATUS_PASS, 'note' => 'Battery still above 86% health.'],
                'wifi' => ['status' => TestResult::STATUS_PASS, 'note' => 'Stable internal network throughput.'],
                'keyboard' => ['status' => TestResult::STATUS_PASS, 'note' => 'Keyboard validated for office assignment.'],
            ],
            'DEMO-008' => [
                'display' => ['status' => TestResult::STATUS_PASS, 'note' => 'Archived unit display still operational.'],
                'battery' => ['status' => TestResult::STATUS_FAIL, 'note' => 'Battery below refurbishment threshold.'],
            ],
            'DEMO-009' => [
                'display' => ['status' => TestResult::STATUS_FAIL, 'note' => 'Intermittent touch ghosting reproduced.'],
                'battery' => ['status' => TestResult::STATUS_PASS, 'note' => 'Battery cycle count acceptable.'],
                'front_camera' => ['status' => TestResult::STATUS_PASS, 'note' => 'Front camera capture validated.'],
            ],
            'DEMO-010' => [
                'display' => ['status' => TestResult::STATUS_PASS, 'note' => 'Panel validated before sale.'],
                'battery' => ['status' => TestResult::STATUS_PASS, 'note' => 'Battery diagnostics clean at dispatch.'],
                'wifi' => ['status' => TestResult::STATUS_PASS, 'note' => 'Wireless connectivity verified before shipment.'],
            ],
        ];

        foreach ($assets as $asset) {
            $testMatrix = $fixtures[$asset->asset_tag] ?? null;

            if (! $testMatrix || ! $standardProfile) {
                continue;
            }

            $isSaleLifecycle = isset($saleStatusIds[$asset->id])
                || Asset::isPreSaleStatus($asset->assetstatus)
                || Asset::isSoldStatus($asset->assetstatus);
            $profiles = WorkflowProfile::query()
                ->active()
                ->forAsset($asset)
                ->where('blocks_sale_readiness', true)
                ->whereHas('items')
                ->ordered()
                ->get();

            if ($profiles->isEmpty()) {
                $profiles = collect([$standardProfile]);
            }

            foreach ($profiles as $profile) {
                $definition = app(WorkflowRunDefinitionService::class)
                    ->forProfile($asset, $profile);
                $run = TestRun::create([
                    'asset_id' => $asset->id,
                    'model_number_id' => $asset->model_number_id,
                    'workflow_profile_id' => $profile->id,
                    'profile_name_snapshot' => $profile->name,
                    'profile_slug_snapshot' => $profile->slug,
                    'readiness_context_hash' => $definition['readiness_context_hash'],
                    'user_id' => $qaUser?->id ?? $asset->created_by,
                    'started_at' => CarbonImmutable::now()->subDays(2),
                    'finished_at' => CarbonImmutable::now()->subDay(),
                ]);

                foreach ($definition['profile_items'] as $profileItem) {
                    $testType = $profileItem->item;
                    $fixture = $testMatrix[$testType->slug] ?? null;
                    $status = $fixture['status']
                        ?? ($isSaleLifecycle && $testType->is_required
                            ? TestResult::STATUS_PASS
                            : TestResult::STATUS_NVT);
                    $note = $fixture['note']
                        ?? ($isSaleLifecycle && $testType->is_required
                            ? 'Demo readiness fixture: required check completed before sale.'
                            : 'Not completed in this demo workflow run.');

                    TestResult::create([
                        'workflow_run_id' => $run->id,
                        'workflow_item_id' => $testType->id,
                        'workflow_profile_item_id' => $profileItem->id,
                        'attribute_definition_id' => $testType->attribute_definition_id,
                        'status' => $status,
                        'note' => $note,
                        'is_required' => $testType->is_required,
                        'result_label_mode' => $testType->result_label_mode ?: 'pass_fail',
                        'sort_order' => $profileItem->sort_order,
                    ]);
                }
            }

            $asset->refresh();
            $asset->refreshTestCompletionFlag();

            if (isset($saleStatusIds[$asset->id])) {
                $asset->status_id = $saleStatusIds[$asset->id];
                $asset->save();
            }
        }
    }

    /**
     * Bump settings.updated_at so hardware index table keys rotate after reseeds.
     * This prevents stale bootstrap-table state from hiding seeded rows.
     */
    private function bumpUiStateVersion(): void
    {
        Setting::query()->update([
            'updated_at' => now(),
        ]);
    }
}

