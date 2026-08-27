<?php

namespace Tests\Feature\Importing\Api;

use App\Models\Import;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class ImportIsolationTest extends ImportDataTestCase
{
    public function testImportIndexOnlyExposesTheCurrentUsersImports(): void
    {
        $owner = User::factory()->canImport()->create();
        $otherUser = User::factory()->canImport()->create();
        $ownedImport = Import::factory()->create([
            'created_by' => $owner->id,
            'first_row' => ['owned@example.test'],
        ]);
        $otherImport = Import::factory()->create([
            'created_by' => $otherUser->id,
            'first_row' => ['private@example.test'],
        ]);

        $this->actingAsForApi($owner)
            ->getJson(route('api.imports.index'))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $ownedImport->id)
            ->assertJsonMissing(['id' => $otherImport->id])
            ->assertJsonMissing(['private@example.test']);

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->getJson(route('api.imports.index'))
            ->assertOk()
            ->assertJsonCount(2);
    }

    public function testForeignImportCannotBeProcessedOrTriggerABackup(): void
    {
        Artisan::shouldReceive('call')->never();

        $owner = User::factory()->canImport()->create();
        $attacker = User::factory()->canImport()->create();
        $import = Import::factory()->accessory()->create([
            'created_by' => $owner->id,
        ]);

        $this->actingAsForApi($attacker);
        $this->importFileResponse([
            'import' => $import->id,
            'import-type' => 'accessory',
            'run-backup' => true,
        ])
            ->assertNotFound()
            ->assertStatusMessageIs('import-errors');

        $this->assertDatabaseHas('imports', [
            'id' => $import->id,
            'created_by' => $owner->id,
        ]);
    }

    public function testInvalidImportTypeIsRejectedBeforeBackupOrProcessing(): void
    {
        Artisan::shouldReceive('call')->never();

        $owner = User::factory()->canImport()->create();
        $import = Import::factory()->create(['created_by' => $owner->id]);

        $this->actingAsForApi($owner);
        $response = $this->importFileResponse([
            'import' => $import->id,
            'import-type' => 'arbitrary',
            'run-backup' => true,
        ]);

        $response
            ->assertOk()
            ->assertStatusMessageIs('error')
            ->assertJsonStructure([
                'messages' => ['import-type'],
            ]);

        $this->assertNull($import->fresh()->import_type);
    }

    public function testBackupFailureStopsImportProcessing(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('snipeit:backup', \Mockery::type('array'))
            ->andReturn(1);

        $owner = User::factory()->canImport()->create();
        $import = Import::factory()->create(['created_by' => $owner->id]);

        $this->actingAsForApi($owner);
        $this->importFileResponse([
            'import' => $import->id,
            'import-type' => 'accessory',
            'run-backup' => true,
        ])
            ->assertInternalServerError()
            ->assertStatusMessageIs('error');

        $this->assertNull($import->fresh()->import_type);
    }

    public function testDemoModeBlocksProcessingAndDeletingStoredImports(): void
    {
        Artisan::shouldReceive('call')->never();
        Storage::fake(config('filesystems.default'));
        config(['app.lock_passwords' => true]);

        $owner = User::factory()->canImport()->create();
        $import = Import::factory()->accessory()->create([
            'created_by' => $owner->id,
            'file_path' => 'demo-mode.csv',
        ]);
        Storage::put('private_uploads/imports/demo-mode.csv', 'Name');

        $this->actingAsForApi($owner);
        $this->importFileResponse([
            'import' => $import->id,
            'import-type' => 'accessory',
            'run-backup' => true,
        ])
            ->assertUnprocessable()
            ->assertStatusMessageIs('error');

        $this->deleteJson(route('api.imports.destroy', $import))
            ->assertUnprocessable()
            ->assertStatusMessageIs('error');

        $this->assertDatabaseHas('imports', ['id' => $import->id]);
        Storage::assertExists('private_uploads/imports/demo-mode.csv');
    }

    public function testDestroyRequiresImportPermissionAndOwnership(): void
    {
        $disk = config('filesystems.default');
        Storage::fake($disk);

        $owner = User::factory()->canImport()->create();
        $import = Import::factory()->create([
            'created_by' => $owner->id,
            'file_path' => 'owned.csv',
        ]);
        Storage::put('private_uploads/imports/owned.csv', 'serial');

        $this->actingAsForApi(User::factory()->createAssets()->create())
            ->deleteJson(route('api.imports.destroy', $import))
            ->assertForbidden();

        $this->actingAsForApi(User::factory()->canImport()->create())
            ->deleteJson(route('api.imports.destroy', $import))
            ->assertNotFound();

        $this->assertDatabaseHas('imports', ['id' => $import->id]);
        Storage::assertExists('private_uploads/imports/owned.csv');

        $this->actingAsForApi($owner)
            ->deleteJson(route('api.imports.destroy', $import))
            ->assertOk()
            ->assertStatusMessageIs('success');

        $this->assertDatabaseMissing('imports', ['id' => $import->id]);
        Storage::assertMissing('private_uploads/imports/owned.csv');
    }

    public function testDeleteFailureRetainsTheImportRecordForRetry(): void
    {
        Storage::shouldReceive('delete')
            ->once()
            ->with('private_uploads/imports/retry.csv')
            ->andReturn(false);

        $owner = User::factory()->canImport()->create();
        $import = Import::factory()->create([
            'created_by' => $owner->id,
            'file_path' => 'retry.csv',
        ]);

        $this->actingAsForApi($owner)
            ->deleteJson(route('api.imports.destroy', $import))
            ->assertInternalServerError()
            ->assertStatusMessageIs('error');

        $this->assertDatabaseHas('imports', ['id' => $import->id]);
    }
}
