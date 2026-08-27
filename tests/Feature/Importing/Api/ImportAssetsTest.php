<?php

namespace Tests\Feature\Importing\Api;

use App\Models\Actionlog as ActionLog;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\CustomField;
use App\Models\Import;
use App\Models\Statuslabel;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\TestsPermissionsRequirement;
use Tests\Support\Importing\AssetsImportFileBuilder as ImportFileBuilder;
use Tests\Support\Importing\CleansUpImportFiles;

class ImportAssetsTest extends ImportDataTestCase implements TestsPermissionsRequirement
{
    use CleansUpImportFiles;
    use WithFaker;

    protected function importFileResponse(array $parameters = []): TestResponse
    {
        if (!array_key_exists('import-type', $parameters)) {
            $parameters['import-type'] = 'asset';
        }

        return parent::importFileResponse($parameters);
    }

    #[Test]
    public function testRequiresPermission()
    {
        $this->actingAsForApi(User::factory()->create());

        $this->importFileResponse(['import' => 44])->assertForbidden();
    }

    #[Test]
    public function userWithImportAssetsPermissionCanImportAssets(): void
    {
        $actor = User::factory()->canImport()->create();
        $this->actingAsForApi($actor);

        $import = Import::factory()->asset()->create(['created_by' => $actor->id]);

        $this->importFileResponse(['import' => $import->id])->assertOk();
    }

    #[Test]
    public function importAsset(): void
    {

        $importFileBuilder = ImportFileBuilder::new(['status' => 'Ready to Deploy']);
        $row = $importFileBuilder->firstRow();
        $import = Import::factory()->asset()->create(['file_path' => $importFileBuilder->saveToImportsDirectory()]);

        $actor = User::factory()->superuser()->create();
        $userCount = User::count();

        $this->actingAsForApi($actor);
        $this->importFileResponse(['import' => $import->id])
            ->assertOk()
            ->assertExactJson([
                'payload'  => null,
                'status'   => 'success',
                'messages' => ['redirect_url' => route('hardware.index')]
            ]);

        $newAsset = Asset::query()
            ->with([
                'location',
                'supplier',
                'company',
                'assignedAssets',
                'defaultLoc',
                'assetStatus',
                'model.category',
                'model.manufacturer',
                'model.primaryModelNumber',
                'modelNumber',
            ])
            ->whereRaw('LOWER(serial) = ?', [Str::lower($row['serialNumber'])])
            ->sole();

        $activityLogs = ActionLog::query()
            ->where('item_type', Asset::class)
            ->where('item_id', $newAsset->id)
            ->get();

        $this->assertCount(1, $activityLogs);
        $this->assertEquals('create', $activityLogs[0]->action_type);
        $this->assertNull($activityLogs[0]->target_id);
        $this->assertEquals(Asset::class, $activityLogs[0]->item_type);
        $this->assertNull($activityLogs[0]->note);
        $this->assertNull($activityLogs[0]->target_type);
        $this->assertHasTheseActionLogs($newAsset, ['create']);
        $this->assertSame($userCount, User::count(), 'Legacy assignee columns must not create a user.');

        $this->assertEquals($row['category'], $newAsset->model->category->name);
        $this->assertEquals($row['manufacturerName'], $newAsset->model->manufacturer->name);
        $this->assertEquals($row['itemName'], $newAsset->name);
        $this->assertSame(Str::upper($row['tag']), $newAsset->asset_tag);
        $this->assertEquals($row['model'], $newAsset->model->name);
        $this->assertEquals($row['modelNumber'], $newAsset->model->model_number);
        $this->assertEquals($row['modelNumber'], $newAsset->modelNumber->code);
        $this->assertTrue($newAsset->modelNumber->is($newAsset->model->primaryModelNumber));
        $this->assertEquals($row['purchaseDate'], $newAsset->purchase_date->toDateString());
        $this->assertNull($newAsset->asset_eol_date);
        $this->assertEquals(0, $newAsset->eol_explicit);
        $this->assertEquals($newAsset->location_id, $newAsset->rtd_location_id);
        $this->assertEquals($row['purchaseCost'], $newAsset->purchase_cost);
        $this->assertNull($newAsset->order_number);
        $this->assertEquals('', $newAsset->image);
        $this->assertNull($newAsset->user_id);
        $this->assertEquals(1, $newAsset->physical);
        $this->assertEquals($row['status'], $newAsset->assetStatus->name);
        $this->assertEquals(0, $newAsset->archived);
        $this->assertEquals($row['warrantyInMonths'], $newAsset->warranty_months);
        $this->assertNull($newAsset->deprecate);
        $this->assertEquals($row['supplierName'], $newAsset->supplier->name);
        $this->assertEquals(0, $newAsset->requestable);
        $this->assertEquals($row['location'], $newAsset->defaultLoc->name);
        $this->assertEquals(null, $newAsset->accepted);
        $this->assertNull($newAsset->last_checkout);
        $this->assertEquals(0, $newAsset->last_checkin);
        $this->assertEquals(0, $newAsset->expected_checkin);
        $this->assertEquals($row['companyName'], $newAsset->company->name);
        $this->assertNull($newAsset->assigned_to);
        $this->assertNull($newAsset->assigned_type);
        $this->assertNull($newAsset->last_audit_date);
        $this->assertNull($newAsset->next_audit_date);
        $this->assertEquals($row['location'], $newAsset->location->name);
        $this->assertEquals(0, $newAsset->checkin_counter);
        $this->assertEquals(0, $newAsset->checkout_counter);
        $this->assertEquals(0, $newAsset->requests_counter);
        $this->assertEquals(0, $newAsset->byod);

        //Notes is never read.
        // $this->assertEquals($row['notes'], $newAsset->notes);

    }

    #[Test]
    public function imported_asset_image_is_confined_to_a_filename(): void
    {
        $baseBuilder = ImportFileBuilder::new(['status' => 'Ready to Deploy']);
        $row = $baseBuilder->firstRow();
        $row['image'] = '../../outside.jpg';
        $importFileBuilder = new ImportFileBuilder([$row]);

        $actor = User::factory()->superuser()->create();
        $import = Import::factory()->asset()->create([
            'created_by' => $actor->id,
            'file_path' => $importFileBuilder->saveToImportsDirectory(),
        ]);

        $this->actingAsForApi($actor);
        $this->importFileResponse(['import' => $import->id])->assertOk();

        $asset = Asset::query()
            ->whereRaw('LOWER(serial) = ?', [Str::lower($row['serialNumber'])])
            ->sole();

        $this->assertSame('outside.jpg', $asset->image);
    }

    #[Test]
    public function assetsWithDifferentModelNumbersShareTheBaseModelAndSelectTheirPreset(): void
    {
        $modelName = 'Shared Model '.Str::random();
        $rows = ImportFileBuilder::times(2)
            ->replace(['model' => $modelName])
            ->all();
        $rows[0]['modelNumber'] = 'VARIANT-A';
        $rows[1]['modelNumber'] = 'VARIANT-B';
        $importFileBuilder = new ImportFileBuilder($rows);
        $import = Import::factory()->asset()->create([
            'file_path' => $importFileBuilder->saveToImportsDirectory(),
        ]);

        $this->actingAsForApi(User::factory()->superuser()->create());
        $this->importFileResponse(['import' => $import->id])->assertOk();

        $model = AssetModel::query()
            ->with(['modelNumbers', 'primaryModelNumber'])
            ->where('name', $modelName)
            ->sole();
        $assets = Asset::query()
            ->with('modelNumber')
            ->whereIn('serial', array_map(
                fn (string $serial) => Str::upper($serial),
                $importFileBuilder->pluck('serialNumber'),
            ))
            ->get()
            ->keyBy('serial');

        $this->assertSame(
            ['VARIANT-A', 'VARIANT-B'],
            $model->modelNumbers->pluck('code')->sort()->values()->all(),
        );
        $this->assertSame('VARIANT-A', $model->primaryModelNumber->code);
        $this->assertSame(
            'VARIANT-A',
            $assets->get(Str::upper($rows[0]['serialNumber']))->modelNumber->code,
        );
        $this->assertSame(
            'VARIANT-B',
            $assets->get(Str::upper($rows[1]['serialNumber']))->modelNumber->code,
        );
    }

    #[Test]
    public function importingASecondaryModelNumberDoesNotReplaceTheExistingPrimary(): void
    {
        $model = AssetModel::factory()->create([
            'name' => 'Existing Model '.Str::random(),
            'model_number' => 'PRIMARY-NUMBER',
        ]);
        $primary = $model->ensurePrimaryModelNumber();
        $importFileBuilder = ImportFileBuilder::new([
            'model' => $model->name,
            'modelNumber' => 'SECONDARY-NUMBER',
        ]);
        $row = $importFileBuilder->firstRow();
        $import = Import::factory()->asset()->create([
            'file_path' => $importFileBuilder->saveToImportsDirectory(),
        ]);

        $this->actingAsForApi(User::factory()->superuser()->create());
        $this->importFileResponse(['import' => $import->id])->assertOk();

        $asset = Asset::query()
            ->with('modelNumber')
            ->where('serial', Str::upper($row['serialNumber']))
            ->sole();
        $model->refresh();

        $this->assertSame($model->id, $asset->model_id);
        $this->assertSame('SECONDARY-NUMBER', $asset->modelNumber->code);
        $this->assertSame($primary->id, $model->primary_model_number_id);
        $this->assertSame('PRIMARY-NUMBER', $model->model_number);
        $this->assertCount(2, $model->modelNumbers);
    }

    #[Test]
    public function willIgnoreUnknownColumnsWhenFileContainsUnknownColumns(): void
    {
        $row = ImportFileBuilder::new()->definition();
        $row['unknownColumnInCsvFile'] = 'foo';

        $importFileBuilder = new ImportFileBuilder([$row]);

        $this->actingAsForApi(User::factory()->superuser()->create());

        $import = Import::factory()->asset()->create(['file_path' => $importFileBuilder->saveToImportsDirectory()]);

        $this->importFileResponse(['import' => $import->id])->assertOk();
    }

    #[Test]
    public function willNotCreateNewAssetWhenAssetWithSameTagAlreadyExists(): void
    {
        $asset = Asset::factory()->create(['asset_tag' => $this->faker->uuid]);
        $importFileBuilder = ImportFileBuilder::times(4)->replace(['tag' => $asset->asset_tag]);
        $import = Import::factory()->asset()->create(['file_path' => $importFileBuilder->saveToImportsDirectory()]);

        $this->actingAsForApi(User::factory()->superuser()->create());
        $this->importFileResponse(['import' => $import->id])
            ->assertInternalServerError()
            ->assertExactJson([
                'status' => 'import-errors',
                'payload' => null,
                'messages' => [
                    '' => [
                        'asset_tag' => [
                            'asset_tag' => [
                                trans('general.import_asset_tag_exists', ['asset_tag' => $asset->asset_tag]),
                            ]
                        ]
                    ]
                ]
            ]);

        $assetsWithSameTag = Asset::query()->where('asset_tag', $asset->asset_tag)->get();

        $this->assertCount(1, $assetsWithSameTag);
    }

    #[Test]
    public function willNotCreateNewCompanyWhenCompanyExists(): void
    {
        $importFileBuilder = ImportFileBuilder::times(4)->replace(['companyName' => Str::random()]);
        $import = Import::factory()->asset()->create(['file_path' => $importFileBuilder->saveToImportsDirectory()]);

        $this->actingAsForApi(User::factory()->superuser()->create());
        $this->importFileResponse(['import' => $import->id])->assertOk();

        $newAssets = Asset::query()
            ->whereIn('serial', array_map(
                fn (string $serial) => Str::upper($serial),
                $importFileBuilder->pluck('serialNumber'),
            ))
            ->get();

        $this->assertCount(1, $newAssets->pluck('company_id')->unique()->all());
    }

    #[Test]
    public function willNotCreateNewLocationWhenLocationExists(): void
    {
        $importFileBuilder = ImportFileBuilder::times(4)->replace(['location' => Str::random()]);
        $import = Import::factory()->asset()->create(['file_path' => $importFileBuilder->saveToImportsDirectory()]);

        $this->actingAsForApi(User::factory()->superuser()->create());
        $this->importFileResponse(['import' => $import->id])->assertOk();

        $newAssets = Asset::query()
            ->whereIn('serial', array_map(
                fn (string $serial) => Str::upper($serial),
                $importFileBuilder->pluck('serialNumber'),
            ))
            ->get();

        $this->assertCount(1, $newAssets->pluck('location_id')->unique()->all());
    }

    #[Test]
    public function willNotCreateNewSupplierWhenSupplierExists(): void
    {
        $importFileBuilder = ImportFileBuilder::times(4)->replace(['supplierName' => $this->faker->company]);
        $import = Import::factory()->asset()->create(['file_path' => $importFileBuilder->saveToImportsDirectory()]);

        $this->actingAsForApi(User::factory()->superuser()->create());
        $this->importFileResponse(['import' => $import->id])->assertOk();

        $newAssets = Asset::query()
            ->whereIn('serial', array_map(
                fn (string $serial) => Str::upper($serial),
                $importFileBuilder->pluck('serialNumber'),
            ))
            ->get(['supplier_id']);

        $this->assertCount(1, $newAssets->pluck('supplier_id')->unique()->all());
    }

    #[Test]
    public function willNotCreateNewManufacturerWhenManufacturerExists(): void
    {
        $importFileBuilder = ImportFileBuilder::times(4)->replace(['manufacturerName' => $this->faker->company]);
        $import = Import::factory()->asset()->create(['file_path' => $importFileBuilder->saveToImportsDirectory()]);

        $this->actingAsForApi(User::factory()->superuser()->create());
        $this->importFileResponse(['import' => $import->id])->assertOk();

        $newAssets = Asset::query()
            ->with('model.manufacturer')
            ->whereIn('serial', array_map(
                fn (string $serial) => Str::upper($serial),
                $importFileBuilder->pluck('serialNumber'),
            ))
            ->get();

        $this->assertCount(1, $newAssets->pluck('model.manufacturer_id')->unique()->all());
    }

    #[Test]
    public function willNotCreateCategoryWhenCategoryExists(): void
    {
        $importFileBuilder = ImportFileBuilder::times(4)->replace(['category' => $this->faker->company]);
        $import = Import::factory()->asset()->create(['file_path' => $importFileBuilder->saveToImportsDirectory()]);

        $this->actingAsForApi(User::factory()->superuser()->create());
        $this->importFileResponse(['import' => $import->id])->assertOk();

        $newAssets = Asset::query()
            ->with('model.category')
            ->whereIn('serial', array_map(
                fn (string $serial) => Str::upper($serial),
                $importFileBuilder->pluck('serialNumber'),
            ))
            ->get();

        $this->assertCount(1, $newAssets->pluck('model.category_id')->unique()->all());
    }

    #[Test]
    public function willNotCreateNewAssetModelWhenAssetModelExists(): void
    {
        $importFileBuilder = ImportFileBuilder::times(4)->replace(['model' => Str::random()]);
        $import = Import::factory()->asset()->create(['file_path' => $importFileBuilder->saveToImportsDirectory()]);

        $this->actingAsForApi(User::factory()->superuser()->create());
        $this->importFileResponse(['import' => $import->id])->assertOk();

        $newAssets = Asset::query()
            ->with('model')
            ->whereIn('serial', array_map(
                fn (string $serial) => Str::upper($serial),
                $importFileBuilder->pluck('serialNumber'),
            ))
            ->get();

        $this->assertCount(1, $newAssets->pluck('model.name')->unique()->all());
    }

    #[Test]
    public function whenColumnsAreMissingInImportFile(): void
    {
        $importFileBuilder = ImportFileBuilder::times()->forget([
            'purchaseCost',
            'purchaseDate',
            'status'
        ]);

        $import = Import::factory()->asset()->create(['file_path' => $importFileBuilder->saveToImportsDirectory()]);

        $this->actingAsForApi(User::factory()->superuser()->create());
        $this->importFileResponse(['import' => $import->id])->assertOk();

        $expectedStatus = Statuslabel::query()
            ->where('default_label', 1)
            ->whereNull('lifecycle_stage')
            ->firstOrFail();
        $newAsset = Asset::query()
            ->with(['assetStatus'])
            ->where('serial', Str::upper($importFileBuilder->firstRow()['serialNumber']))
            ->sole();

        $this->assertTrue($newAsset->assetStatus->is($expectedStatus));
        $this->assertFalse(Asset::statusRequiresTestAck($newAsset->assetStatus));
        $this->assertNull($newAsset->purchase_date);
        $this->assertNull($newAsset->purchase_cost);
    }

    #[Test]
    public function willFormatValues(): void
    {
        $importFileBuilder = ImportFileBuilder::new([
            'warrantyInMonths' => '3 months',
            'purchaseDate'    => '2022/10/10'
        ]);

        $import = Import::factory()->asset()->create(['file_path' => $importFileBuilder->saveToImportsDirectory()]);

        $this->actingAsForApi(User::factory()->superuser()->create());
        $this->importFileResponse(['import' => $import->id])->assertOk();

        $newAsset = Asset::query()
            ->where('serial', Str::upper($importFileBuilder->firstRow()['serialNumber']))
            ->sole();

        $this->assertEquals(3, $newAsset->warranty_months);
        $this->assertEquals('2022-10-10', $newAsset->purchase_date->toDateString());
    }

    #[Test]
    public function missingTagAndModelNameUseGeneratedTagAndNullableModel(): void
    {
        $importFileBuilder = ImportFileBuilder::times(2)
            ->forget(['tag'])
            ->replace(['model' => '']);

        $rows = $importFileBuilder->all();
        $import = Import::factory()->asset()->create(['file_path' => $importFileBuilder->saveToImportsDirectory()]);
        $actor = User::factory()->superuser()->create();
        $userCount = User::count();

        $this->actingAsForApi($actor);
        $this->importFileResponse(['import' => $import->id])->assertOk();

        $newAssets = Asset::query()
            ->whereIn('serial', array_map(
                fn (string $serial) => Str::upper($serial),
                Arr::pluck($rows, 'serialNumber'),
            ))
            ->get();

        $this->assertCount(2, $newAssets);
        $this->assertCount(2, $newAssets->pluck('asset_tag')->unique());
        foreach ($newAssets as $asset) {
            $this->assertMatchesRegularExpression('/^INBIT-[A-Z]{2}\d{4}$/', $asset->asset_tag);
            $this->assertNull($asset->model_id);
            $this->assertNull($asset->assigned_to);
            $this->assertNull($asset->assigned_type);
        }
        $this->assertSame($userCount, User::count());
    }

    #[Test]
    public function updateAssetFromImport(): void
    {
        $asset = Asset::factory()->create()->refresh();
        $importFileBuilder = ImportFileBuilder::times(1)->replace(['tag' => $asset->asset_tag]);
        $row = $importFileBuilder->firstRow();
        $import = Import::factory()->asset()->create(['file_path' => $importFileBuilder->saveToImportsDirectory()]);

        $actor = User::factory()->superuser()->create();
        $userCount = User::count();

        $this->actingAsForApi($actor);
        $this->importFileResponse(['import' => $import->id, 'import-update' => true])->assertOk();

        $updatedAsset = Asset::query()
            ->with(['location', 'supplier', 'company', 'defaultLoc', 'assetStatus', 'model.category', 'model.manufacturer'])
            ->find($asset->id);

        $updatedAttributes = array_merge([
            'category', 'category_id', 'manufacturer_id', 'name', 'tag', 'model_id',
            'model_number', 'model_number_id', 'purchase_date', 'purchase_cost', 'warranty_months', 'supplier_id',
            'location_id', 'company_id', 'serial', 'status_id', 'rtd_location_id',
            'archived', 'is_sellable', 'updated_at',
        ], Asset::LEGACY_READ_ONLY_FIELDS);

        $this->assertEquals($row['category'], $updatedAsset->model->category->name);
        $this->assertEquals($row['manufacturerName'], $updatedAsset->model->manufacturer->name);
        $this->assertEquals($row['itemName'], $updatedAsset->name);
        $this->assertSame(Str::upper($row['tag']), $updatedAsset->asset_tag);
        $this->assertEquals($row['model'], $updatedAsset->model->name);
        $this->assertEquals($row['modelNumber'], $updatedAsset->model->model_number);
        $this->assertEquals($row['modelNumber'], $updatedAsset->modelNumber->code);
        $this->assertEquals($row['purchaseDate'], $updatedAsset->purchase_date->toDateString());
        $this->assertEquals($row['purchaseCost'], $updatedAsset->purchase_cost);
        $this->assertEquals($row['status'], $updatedAsset->assetStatus->name);
        $this->assertEquals($row['warrantyInMonths'], $updatedAsset->warranty_months);
        $this->assertEquals($row['supplierName'], $updatedAsset->supplier->name);
        $this->assertEquals($row['location'], $updatedAsset->defaultLoc->name);
        $this->assertEquals($row['companyName'], $updatedAsset->company->name);
        $this->assertEquals($row['location'], $updatedAsset->location->name);
        $this->assertNull($updatedAsset->assigned_to);
        $this->assertNull($updatedAsset->assigned_type);
        $this->assertEquals(0, $updatedAsset->checkout_counter);
        $this->assertSame($userCount, User::count(), 'Legacy assignee columns must not create a user.');
        $this->assertFalse(
            $updatedAsset->assetlog()->where('action_type', 'checkout')->exists(),
            'Asset imports must not synthesize checkout history.'
        );
        foreach (Asset::LEGACY_READ_ONLY_FIELDS as $field) {
            $this->assertSame(
                $asset->getRawOriginal($field),
                $updatedAsset->getRawOriginal($field),
                "Asset imports must preserve historical [{$field}] metadata.",
            );
        }

        $this->assertEquals(
            Arr::except($asset->attributesToArray(), $updatedAttributes),
            Arr::except($updatedAsset->attributesToArray(), $updatedAttributes),
        );
    }

    #[Test]
    public function updateImportCanClearTheByodFlag(): void
    {
        $asset = Asset::factory()->create(['byod' => true]);
        $row = ImportFileBuilder::new()->firstRow();
        $row['tag'] = $asset->asset_tag;
        $row['byod'] = 'FALSE';
        $importFileBuilder = new ImportFileBuilder([$row]);
        $import = Import::factory()->asset()->create([
            'file_path' => $importFileBuilder->saveToImportsDirectory(),
        ]);

        $this->actingAsForApi(User::factory()->superuser()->create());
        $this->importFileResponse([
            'import' => $import->id,
            'import-update' => true,
        ])->assertOk();

        $this->assertFalse((bool) $asset->fresh()->byod);
    }

    #[Test]
    public function updateImportPreservesTheByodFlagWhenTheColumnIsMissing(): void
    {
        $asset = Asset::factory()->create(['byod' => true]);
        $importFileBuilder = ImportFileBuilder::new(['tag' => $asset->asset_tag]);
        $import = Import::factory()->asset()->create([
            'file_path' => $importFileBuilder->saveToImportsDirectory(),
        ]);

        $this->actingAsForApi(User::factory()->superuser()->create());
        $this->importFileResponse([
            'import' => $import->id,
            'import-update' => true,
        ])->assertOk();

        $this->assertTrue((bool) $asset->fresh()->byod);
    }

    #[Test]
    public function invalidPurchaseDateDoesNotBecomeTheUnixEpoch(): void
    {
        $importFileBuilder = ImportFileBuilder::new([
            'purchaseDate' => 'definitely-not-a-date',
        ]);
        $row = $importFileBuilder->firstRow();
        $import = Import::factory()->asset()->create([
            'file_path' => $importFileBuilder->saveToImportsDirectory(),
        ]);

        $this->actingAsForApi(User::factory()->superuser()->create());
        $this->importFileResponse(['import' => $import->id])->assertOk();

        $asset = Asset::query()
            ->where('serial', Str::upper($row['serialNumber']))
            ->sole();

        $this->assertNull($asset->purchase_date);
    }

    #[Test]
    public function assetImportMappingsCannotWriteLegacyMetadataOrCreateAnAssignee(): void
    {
        $asset = Asset::factory()->create(['name' => 'Historical Asset']);
        $legacyValues = [
            'requestable' => 1,
            'last_checkin' => '2024-01-02 03:04:05',
            'last_checkout' => '2024-01-03 04:05:06',
            'expected_checkin' => '2024-02-01',
            'last_audit_date' => '2024-01-04 05:06:07',
            'next_audit_date' => '2024-03-01',
        ];
        DB::table('assets')->where('id', $asset->id)->update($legacyValues);
        $historicalSnapshot = (array) DB::table('assets')
            ->where('id', $asset->id)
            ->first(Asset::LEGACY_READ_ONLY_FIELDS);

        $row = ImportFileBuilder::new()->firstRow();
        $row['tag'] = $asset->asset_tag;
        $row['Legacy Requestable'] = '0';
        $row['Legacy Last Checkin'] = '2030-01-02 03:04:05';
        $row['Legacy Last Checkout'] = '2030-01-03 04:05:06';
        $row['Legacy Expected Checkin'] = '2030-02-01';
        $row['Legacy Last Audit'] = '2030-01-04 05:06:07';
        $row['Legacy Next Audit'] = '2030-03-01';
        $row['Legacy Checkout Type'] = 'user';
        $row['Legacy Checkout Location'] = 'Must Not Exist';
        $row['Legacy Full Name'] = 'Must Not Exist';
        $row['Legacy Email'] = 'must-not-exist@example.test';
        $row['Legacy Username'] = 'must-not-exist';
        $row['Legacy Assigned To'] = '999999';
        $row['Legacy Assigned Type'] = User::class;

        $importFileBuilder = new ImportFileBuilder([$row]);
        $import = Import::factory()->asset()->create([
            'file_path' => $importFileBuilder->saveToImportsDirectory(),
        ]);
        $actor = User::factory()->superuser()->create();
        $userCount = User::count();
        $legacyLogCount = ActionLog::query()
            ->where('item_type', Asset::class)
            ->where('item_id', $asset->id)
            ->where(function ($query) {
                $query->where('action_type', 'checkout')
                    ->orWhere('action_type', 'like', 'checkin%')
                    ->orWhere('action_type', 'audit');
            })
            ->count();

        $this->actingAsForApi($actor);
        $this->importFileResponse([
            'import' => $import->id,
            'import-update' => true,
            'column-mappings' => [
                'Legacy Requestable' => 'requestable',
                'Legacy Last Checkin' => 'last_checkin',
                'Legacy Last Checkout' => 'last_checkout',
                'Legacy Expected Checkin' => 'expected_checkin',
                'Legacy Last Audit' => 'last_audit_date',
                'Legacy Next Audit' => 'next_audit_date',
                'Legacy Checkout Type' => 'checkout_class',
                'Legacy Checkout Location' => 'checkout_location',
                'Legacy Full Name' => 'full_name',
                'Legacy Email' => 'email',
                'Legacy Username' => 'username',
                'Legacy Assigned To' => 'assigned_to',
                'Legacy Assigned Type' => 'assigned_type',
            ],
        ])->assertOk();

        $this->assertSame(
            $historicalSnapshot,
            (array) DB::table('assets')
                ->where('id', $asset->id)
                ->first(Asset::LEGACY_READ_ONLY_FIELDS),
        );
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'assigned_to' => null,
            'assigned_type' => null,
        ]);
        $this->assertSame($userCount, User::count());
        $this->assertSame(
            $legacyLogCount,
            ActionLog::query()
                ->where('item_type', Asset::class)
                ->where('item_id', $asset->id)
                ->where(function ($query) {
                    $query->where('action_type', 'checkout')
                        ->orWhere('action_type', 'like', 'checkin%')
                        ->orWhere('action_type', 'audit');
                })
                ->count(),
        );
    }

    #[Test]
    public function customColumnMapping(): void
    {
        $faker = ImportFileBuilder::new()->definition();
        $row = [
            'assigneeFullName'    => $faker['supplierName'],
            'assigneeEmail'       => $faker['manufacturerName'],
            'assigneeUsername'    => $faker['serialNumber'],
            'category'            => $faker['location'],
            'companyName'         => $faker['purchaseCost'],
            'itemName'            => $faker['modelNumber'],
            'location'            => $faker['assigneeUsername'],
            // Keep the shuffled status mapping deterministic. The builder's
            // random Archived value correctly archives the imported asset,
            // which conflicts with this test's non-archived field assertions.
            'manufacturerName'    => 'Ready to Deploy',
            'model'               => $faker['itemName'],
            'modelNumber'         => $faker['category'],
            'notes'               => $faker['notes'],
            'purchaseCost'        => $faker['model'],
            'purchaseDate'        => $faker['companyName'],
            'serialNumber'        => $faker['tag'],
            'supplierName'        => $faker['purchaseDate'],
            'status'              => $faker['warrantyInMonths'],
            'tag'                 => $faker['assigneeEmail'],
            'warrantyInMonths'    => $faker['assigneeFullName'],
        ];

        $importFileBuilder = new ImportFileBuilder([$row]);
        $import = Import::factory()->asset()->create(['file_path' => $importFileBuilder->saveToImportsDirectory()]);

        $actor = User::factory()->superuser()->create();
        $userCount = User::count();

        $this->actingAsForApi($actor);

        $this->importFileResponse([
            'import' => $import->id,
            'column-mappings' => [
                'Asset Tag'     => 'email',
                'Category'      => 'location',
                'Company'       => 'purchase_cost',
                'Email'         => 'manufacturer',
                'Full Name'     => 'supplier',
                'Item Name'     => 'model_number',
                'Location'      => 'username',
                'Manufacturer'  => 'status',
                'Model name'    => 'item_name',
                'Model Number'  => 'category',
                'Notes'         => 'asset_notes',
                'Purchase Cost' => 'asset_model',
                'Purchase Date' => 'company',
                'Serial number' => 'asset_tag',
                'Status'        => 'warranty_months',
                'Supplier'      => 'purchase_date',
                'Username'      => 'serial',
                'Warranty'      => 'full_name',
            ]
        ])->assertOk();

        $asset = Asset::query()
            ->with(['location', 'supplier', 'company', 'assignedAssets', 'defaultLoc', 'assetStatus', 'model.category', 'model.manufacturer'])
            ->whereRaw('LOWER(serial) = ?', [Str::lower($row['assigneeUsername'])])
            ->sole();

        $this->assertEquals($row['modelNumber'], $asset->model->category->name);
        $this->assertEquals($row['assigneeEmail'], $asset->model->manufacturer->name);
        $this->assertEquals($row['model'], $asset->name);
        $this->assertSame(Str::upper($row['serialNumber']), $asset->asset_tag);
        $this->assertEquals($row['purchaseCost'], $asset->model->name);
        $this->assertEquals($row['itemName'], $asset->model->model_number);
        $this->assertEquals($row['supplierName'], $asset->purchase_date->toDateString());
        $this->assertEquals($row['companyName'], $asset->purchase_cost);
        $this->assertEquals($row['manufacturerName'], $asset->assetStatus->name);
        $this->assertEquals($row['status'], $asset->warranty_months);
        $this->assertEquals($row['assigneeFullName'], $asset->supplier->name);
        $this->assertEquals($row['category'], $asset->defaultLoc->name);
        $this->assertEquals($row['purchaseDate'], $asset->company->name);
        $this->assertEquals($row['category'], $asset->location->name);
        $this->assertEquals($row['notes'], $asset->notes);
        $this->assertNull($asset->asset_eol_date);
        $this->assertEquals(0, $asset->eol_explicit);
        $this->assertNull($asset->order_number);
        $this->assertEquals('', $asset->image);
        $this->assertNull($asset->user_id);
        $this->assertEquals(1, $asset->physical);
        $this->assertEquals(0, $asset->archived);
        $this->assertNull($asset->deprecate);
        $this->assertEquals(0, $asset->requestable);
        $this->assertEquals(null, $asset->accepted);
        $this->assertNull($asset->last_checkout);
        $this->assertEquals(0, $asset->last_checkin);
        $this->assertEquals(0, $asset->expected_checkin);
        $this->assertNull($asset->assigned_to);
        $this->assertNull($asset->assigned_type);
        $this->assertNull($asset->last_audit_date);
        $this->assertNull($asset->next_audit_date);
        $this->assertEquals(0, $asset->checkin_counter);
        $this->assertEquals(0, $asset->checkout_counter);
        $this->assertEquals(0, $asset->requests_counter);
        $this->assertEquals(0, $asset->byod);
        $this->assertSame($userCount, User::count(), 'Legacy assignee columns must not create a user.');
        $this->assertFalse(
            $asset->assetlog()->where('action_type', 'checkout')->exists(),
            'Asset imports must not synthesize checkout history.'
        );
    }

    #[Test]
    public function customFields(): void
    {
        $macAddress = $this->faker->macAddress;

        $row = ImportFileBuilder::new()->definition();
        $row['Mac Address'] = $macAddress;

        $importFileBuilder = new ImportFileBuilder([$row]);
        $customField = CustomField::query()->where('name', 'Mac Address')->firstOrNew();

        if (!$customField->exists) {
            $customField = CustomField::factory()->macAddress()->create(['db_column' => '_snipeit_mac_address_1']);
        }

        if ($customField->field_encrypted) {
            $customField->field_encrypted = 0;
            $customField->save();
        }

        $import = Import::factory()->asset()->create(['file_path' => $importFileBuilder->saveToImportsDirectory()]);

        $this->actingAsForApi(User::factory()->superuser()->create());
        $this->importFileResponse(['import' => $import->id])->assertOk();

        $newAsset = Asset::query()
            ->where('serial', Str::upper($importFileBuilder->firstRow()['serialNumber']))
            ->sole();

        $this->assertEquals($macAddress, $newAsset->getAttribute($customField->db_column));
    }

    #[Test]
    public function willEncryptCustomFields(): void
    {
        $macAddress = $this->faker->macAddress;
        $row = ImportFileBuilder::new()->definition();

        $row['Mac Address'] = $macAddress;

        $importFileBuilder = new ImportFileBuilder([$row]);
        $customField = CustomField::query()->where('name', 'Mac Address')->firstOrNew();

        if (!$customField->exists) {
            $customField = CustomField::factory()->macAddress()->create();
        }

        if (!$customField->field_encrypted) {
            $customField->field_encrypted = 1;
            $customField->save();
        }

        $import = Import::factory()->asset()->create(['file_path' => $importFileBuilder->saveToImportsDirectory()]);

        $this->actingAsForApi(User::factory()->superuser()->create());
        $this->importFileResponse(['import' => $import->id])->assertOk();

        $asset = Asset::query()
            ->where('serial', Str::upper($importFileBuilder->firstRow()['serialNumber']))
            ->sole();
        $encryptedMacAddress = $asset->getAttribute($customField->db_column);

        $this->assertNotEquals($encryptedMacAddress, $macAddress);
    }
}

