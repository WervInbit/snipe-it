<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\ModelNumberAttribute;
use App\Models\Category;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\Statuslabel;
use App\Models\Supplier;
use App\Models\TestResult;
use App\Models\TestRun;
use App\Models\TestType;
use App\Models\User;
use App\Services\ModelAttributes\AttributeValueService;
use App\Services\QrLabelService;
use Carbon\CarbonImmutable;
use Database\Seeders\Concerns\ProvidesDeviceCatalogData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DemoAssetsSeeder extends Seeder
{
    use ProvidesDeviceCatalogData;

    public function run(): void
    {
        $this->resetTables();

        $admin = User::where('permissions->superuser', '1')->first();

        if ($admin) {
            Auth::login($admin);
        }

        $models = $this->seedModelBlueprints();
        $assets = $this->seedAssets($models);
        $this->seedTestRuns($assets);

        if ($admin) {
            Auth::logout();
        }
    }

    /**
     * Remove existing asset/test data so the curated dataset stays small.
     */
    private function resetTables(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ([
            'test_results',
            'test_runs',
            'test_audits',
            'asset_tests',
            'asset_logs',
            'asset_images',
            'asset_status_history',
            'checkout_requests',
            'assets',
        ] as $table) {
            DB::table($table)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
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
     * @return array<int,\App\Models\Asset>
     */
    private function seedAssets(array $models): array
    {
        $status = Statuslabel::query()->pluck('id', 'name');
        $locations = Location::query()->pluck('id', 'name');
        $suppliers = Supplier::query()->pluck('id', 'name');
        $users = User::query()->pluck('id', 'username');

        $qr = app(QrLabelService::class);

        $assets = [];
        $records = [
            [
                'tag' => 'DEMO-001',
                'name' => 'HP ProBook 450 G8',
                'model_key' => 'HP ProBook 450 G8',
                'status' => 'Ready for Sale',
                'location' => 'Ready to Ship',
                'notes' => 'All refurb checks cleared; staged for sales.',
                'assigned_to' => null,
                'supplier' => $suppliers->first(),
            ],
            [
                'tag' => 'DEMO-002',
                'name' => 'HP ProBook 430 G7',
                'model_key' => 'HP ProBook 430 G7',
                'status' => 'Being Processed',
                'location' => 'Repair Bench',
                'notes' => 'Battery cycle validation pending before QA hand-off.',
                'assigned_to' => null,
                'supplier' => $suppliers->first(),
            ],
            [
                'tag' => 'DEMO-003',
                'name' => 'Samsung Galaxy A5',
                'model_key' => 'Samsung Galaxy A5',
                'status' => 'Stand-by',
                'location' => 'Refurb Intake',
                'notes' => 'Awaiting replacement battery calibration and cosmetic check.',
                'assigned_to' => null,
                'supplier' => $suppliers->get('Renewed Supply Co.') ?? $suppliers->first(),
            ],
            [
                'tag' => 'DEMO-004',
                'name' => 'Pixel 8 Pro',
                'model_key' => 'Pixel 8 Pro',
                'status' => 'Ready for Sale',
                'location' => 'Ready to Ship',
                'notes' => 'Flagship Android phone prepped for ecommerce batch.',
                'assigned_to' => null,
                'supplier' => $suppliers->get('Renewed Supply Co.') ?? $suppliers->first(),
            ],
        ];

        foreach ($records as $record) {
            $catalog = $models[$record['model_key']] ?? null;

            if (! $catalog) {
                continue;
            }

            $statusName = $record['status'];
            $statusId = $status->get($statusName);
            $testsOk = $statusName && str_contains(strtolower($statusName), 'ready for sale');

            $asset = Asset::factory()->create([
                'asset_tag' => $record['tag'],
                'name' => $record['name'],
                'notes' => $record['notes'],
                'model_id' => $catalog['model']->id,
                'model_number_id' => $catalog['model_number_id'],
                'status_id' => $statusId,
                'tests_completed_ok' => $testsOk,
                'rtd_location_id' => $locations->get($record['location']),
                'supplier_id' => $record['supplier'],
                'purchase_date' => CarbonImmutable::now()->subMonths(2)->format('Y-m-d'),
                'purchase_cost' => 0,
                'assigned_to' => $record['assigned_to'],
                'assigned_type' => $record['assigned_to'] ? User::class : null,
                'created_by' => $users->get('admin'),
            ]);

            try {
                $qr->generate($asset);
            } catch (\Throwable) {
                // Best-effort QR generation; ignore failures in demo data.
            }

            $assets[] = $asset;
        }

        return $assets;
    }

    /**
     * Attach concise test history to the demo assets.
     *
     * @param array<int,\App\Models\Asset> $assets
     */
    private function seedTestRuns(array $assets): void
    {
        $testTypes = TestType::query()->pluck('id', 'slug');
        $qaUser = User::where('username', 'qa_manager')->first();

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
        ];

        foreach ($assets as $asset) {
            $testMatrix = $fixtures[$asset->asset_tag] ?? null;

            if (! $testMatrix) {
                continue;
            }

            $run = TestRun::create([
                'asset_id' => $asset->id,
                'model_number_id' => $asset->model_number_id,
                'user_id' => $qaUser?->id ?? $asset->created_by,
                'started_at' => CarbonImmutable::now()->subDays(2),
                'finished_at' => CarbonImmutable::now()->subDay(),
            ]);

            foreach ($testMatrix as $slug => $result) {
                $testTypeId = $testTypes->get($slug);

                if (! $testTypeId) {
                    continue;
                }

                TestResult::create([
                    'test_run_id' => $run->id,
                    'test_type_id' => $testTypeId,
                    'status' => $result['status'],
                    'note' => $result['note'],
                ]);
            }

            $asset->refresh();
            $asset->refreshTestCompletionFlag();
        }
    }
}

