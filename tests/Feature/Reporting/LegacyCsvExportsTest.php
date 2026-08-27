<?php

namespace Tests\Feature\Reporting;

use App\Models\Accessory;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Depreciation;
use App\Models\License;
use App\Models\Maintenance;
use App\Models\ModelNumber;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use League\Csv\Reader;
use Tests\TestCase;

class LegacyCsvExportsTest extends TestCase
{
    public function testAccessoryExportUsesCurrentFieldsAndProducesSafeCsv(): void
    {
        $accessory = Accessory::factory()->create([
            'name' => '=2+2',
            'qty' => 3,
        ]);
        $accessory->category->update(['name' => 'Adapters, USB']);

        $rows = $this->csvRows(
            $this->actingAs(User::factory()->canViewReports()->create())
                ->get(route('reports/export/accessories'))
                ->assertOk()
        );

        $this->assertCount(2, $rows);
        $this->assertSame('`=2+2', $rows[1][0]);
        $this->assertSame('Adapters, USB', $rows[1][1]);
        $this->assertSame('3', $rows[1][2]);
        $this->assertSame('3', $rows[1][3]);
    }

    public function testLicenseExportHandlesNullableDepreciationAndEscapesFormulas(): void
    {
        $depreciation = Depreciation::factory()->create(['name' => 'Straight, Line']);
        License::factory()->create([
            'name' => '=Licensed',
            'depreciation_id' => $depreciation->id,
            'purchase_cost' => 1234.56,
        ]);
        License::factory()->create([
            'name' => 'No Depreciation',
            'depreciation_id' => null,
        ]);

        $rows = $this->csvRows(
            $this->actingAs(User::factory()->canViewReports()->viewLicenses()->create())
                ->get(route('reports/export/licenses'))
                ->assertOk()
        );
        $records = collect(array_slice($rows, 1))->keyBy(0);

        $this->assertSame('Straight, Line', $records->get('`=Licensed')[6]);
        $this->assertSame('', $records->get('No Depreciation')[6]);
        $this->assertCount(8, $records->get('`=Licensed'));
    }

    public function testLicenseReportRequiresLicenseAccessAndHidesKeysWithoutKeyPermission(): void
    {
        License::factory()->create([
            'name' => 'Restricted entitlement',
            'serial' => 'REPORT-SECRET-KEY',
        ]);

        $this->actingAs(User::factory()->canViewReports()->create())
            ->get(route('reports/licenses'))
            ->assertForbidden();

        $this->actingAs(User::factory()->canViewReports()->create())
            ->get(route('reports/export/licenses'))
            ->assertForbidden();

        $metadataViewer = User::factory()->canViewReports()->viewLicenses()->create();

        $this->actingAs($metadataViewer)
            ->get(route('reports/licenses'))
            ->assertOk()
            ->assertDontSee('REPORT-SECRET-KEY');

        $metadataRows = $this->csvRows(
            $this->actingAs($metadataViewer)
                ->get(route('reports/export/licenses'))
                ->assertOk()
        );
        $metadataRecord = collect(array_slice($metadataRows, 1))->firstWhere(0, 'Restricted entitlement');

        $this->assertNotNull($metadataRecord);
        $this->assertSame('', $metadataRecord[1]);

        $keyViewer = User::factory()->canViewReports()->viewLicenses()->viewKeysLicenses()->create();
        $keyRows = $this->csvRows(
            $this->actingAs($keyViewer)
                ->get(route('reports/export/licenses'))
                ->assertOk()
        );
        $keyRecord = collect(array_slice($keyRows, 1))->firstWhere(0, 'Restricted entitlement');

        $this->assertNotNull($keyRecord);
        $this->assertSame('REPORT-SECRET-KEY', $keyRecord[1]);
    }

    public function testMaintenanceExportHandlesMissingSupplierAndUsesCanonicalType(): void
    {
        Maintenance::factory()->create([
            'supplier_id' => null,
            'asset_maintenance_type' => 'calibration',
            'name' => '=Bench calibration',
            'start_date' => '2026-07-01',
            'completion_date' => '2026-07-02',
            'asset_maintenance_time' => 1,
        ]);

        $rows = $this->csvRows(
            $this->actingAs(User::factory()->canViewReports()->create())
                ->get(route('reports/export/maintenances'))
                ->assertOk()
        );

        $this->assertCount(2, $rows);
        $this->assertSame('', $rows[1][2]);
        $this->assertSame('calibration', $rows[1][3]);
        $this->assertSame('`=Bench calibration', $rows[1][4]);
        $this->assertCount(9, $rows[1]);
    }

    public function testCustomReportUsesTheAssetSelectedModelNumber(): void
    {
        $model = AssetModel::factory()->create(['model_number' => 'LEGACY-NUMBER']);
        $selectedNumber = ModelNumber::factory()
            ->for($model, 'model')
            ->create(['code' => 'SELECTED-NUMBER']);
        Asset::factory()->create([
            'model_id' => $model->id,
            'model_number_id' => $selectedNumber->id,
        ]);

        $rows = $this->csvRows(
            $this->actingAs(User::factory()->canViewReports()->create())
                ->post(route('reports.post-custom'), ['model' => '1'])
                ->assertOk()
        );

        $this->assertSame('SELECTED-NUMBER', $rows[1][1]);
        $this->assertNotSame('LEGACY-NUMBER', $rows[1][1]);
    }

    public function testActivityExportAlignsMetadataColumnsAndUsesSelectedModelNumber(): void
    {
        $model = AssetModel::factory()->create(['model_number' => 'LEGACY-NUMBER']);
        $selectedNumber = ModelNumber::factory()
            ->for($model, 'model')
            ->create(['code' => 'SELECTED-NUMBER']);
        $asset = Asset::factory()->create([
            'model_id' => $model->id,
            'model_number_id' => $selectedNumber->id,
        ]);
        Actionlog::factory()->create([
            'item_id' => $asset->id,
            'item_type' => Asset::class,
            'note' => '=2+2',
            'remote_ip' => '192.0.2.10',
            'user_agent' => 'Regression, Browser',
            'action_source' => 'api',
            'log_meta' => '{"changed":"yes"}',
        ]);

        $rows = $this->csvRows(
            $this->actingAs(User::factory()->canViewReports()->create())
                ->post(route('reports.activity.post'))
                ->assertOk()
        );
        $record = collect(array_slice($rows, 1))
            ->first(fn (array $row) => ($row[10] ?? null) === '192.0.2.10');

        $this->assertNotNull($record);
        $this->assertCount(14, $record);
        $this->assertSame('SELECTED-NUMBER', $record[7]);
        $this->assertSame('`=2+2', $record[9]);
        $this->assertSame('192.0.2.10', $record[10]);
        $this->assertSame('Regression, Browser', $record[11]);
        $this->assertSame('api', $record[12]);
        $this->assertSame('{"changed":"yes"}', $record[13]);
    }

    public function testLicenseInventoryExportEscapesFormulaCells(): void
    {
        $license = License::factory()->create([
            'name' => '=Injected License',
            'notes' => '@SUM(1+1)',
        ]);

        $rows = $this->csvRows(
            $this->actingAs(User::factory()->viewLicenses()->create())
                ->get(route('licenses.export'))
                ->assertOk()
        );
        $record = collect(array_slice($rows, 1))
            ->first(fn (array $row) => ($row[0] ?? null) === (string) $license->id);

        $this->assertNotNull($record);
        $this->assertSame('`=Injected License', $record[2]);
        $this->assertSame('', $record[3]);
        $this->assertSame('`@SUM(1+1)', $record[24]);

        $keyRows = $this->csvRows(
            $this->actingAs(User::factory()->viewLicenses()->viewKeysLicenses()->create())
                ->get(route('licenses.export'))
                ->assertOk()
        );
        $keyRecord = collect(array_slice($keyRows, 1))
            ->first(fn (array $row) => ($row[0] ?? null) === (string) $license->id);

        $this->assertNotNull($keyRecord);
        $this->assertSame($license->serial, $keyRecord[3]);
    }

    public function testUserInventoryExportEscapesFormulaCellsAndWritesOneHeader(): void
    {
        $user = User::factory()->create([
            'jobtitle' => '=Injected Job',
            'first_name' => '+Injected Name',
            'notes' => '@SUM(1+1)',
        ]);
        $actor = User::factory()->viewUsers()->create();
        $now = now();

        foreach (array_chunk(range(1, 500), 50) as $batch) {
            DB::table('users')->insert(array_map(
                fn (int $index): array => [
                    'email' => "csv-export-{$index}@example.test",
                    'password' => 'not-used',
                    'permissions' => '{}',
                    'activated' => 1,
                    'first_name' => 'CSV',
                    'last_name' => 'Filler',
                    'username' => "csv-export-{$index}",
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                $batch,
            ));
        }

        $rows = $this->csvRows(
            $this->actingAs($actor)
                ->get(route('users.export'))
                ->assertOk()
        );
        $record = collect(array_slice($rows, 1))
            ->first(fn (array $row) => ($row[7] ?? null) === $user->username);
        $header = $rows[0];

        $this->assertNotNull($record);
        $this->assertSame('`=Injected Job', $record[2]);
        $this->assertSame('`+Injected Name', $record[4]);
        $this->assertSame('`@SUM(1+1)', $record[18]);
        $this->assertSame(1, collect($rows)->filter(
            fn (array $row) => $row === $header
        )->count());
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    private function csvRows(TestResponse $response): array
    {
        return iterator_to_array(
            Reader::createFromString($response->streamedContent())->getRecords(),
            false
        );
    }
}
