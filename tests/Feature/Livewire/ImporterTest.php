<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Importer;
use App\Models\Asset;
use App\Models\Import;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ImporterTest extends TestCase
{
    public function testRendersSuccessfully()
    {
        Livewire::actingAs(User::factory()->canImport()->create())
            ->test(Importer::class)
            ->assertStatus(200);
    }

    public function testRequiresPermission()
    {
        Livewire::actingAs(User::factory()->create())
            ->test(Importer::class)
            ->assertStatus(403);
    }

    public function testSelectFileAccessibilityLabelUsesAnExistingTranslation(): void
    {
        Livewire::actingAs(User::factory()->canImport()->create())
            ->test(Importer::class)
            ->assertSee(trans('button.select_file'))
            ->assertDontSee('admin/importer/general.select_file');
    }

    public function testAssetImportUiAndAutomaticMappingExcludeLegacyLifecycleFields(): void
    {
        $component = Livewire::actingAs(User::factory()->canImport()->create())
            ->test(Importer::class)
            ->assertStatus(200)
            ->instance();

        $forbiddenAssetFields = array_merge(Asset::LEGACY_READ_ONLY_FIELDS, [
            'checkout_class',
            'checkout_location',
            'full_name',
            'first_name',
            'last_name',
            'email',
            'username',
            'assigned_to',
            'assigned_type',
        ]);

        foreach ($forbiddenAssetFields as $field) {
            $this->assertArrayNotHasKey($field, $component->assets_fields);
            $this->assertArrayNotHasKey($field, $component->columnOptions['asset']);
        }

        $component->headerRow = [
            'Requestable',
            'Last Checkin',
            'Last Checkout',
            'Expected Checkin',
            'Last Audit Date',
            'Next Audit Date',
            'Checkout Type',
            'Checkout Location',
            'Full Name',
            'First Name',
            'Last Name',
            'Email',
            'Username',
        ];
        $component->field_map = [];
        $component->updatingTypeOfImport('asset');

        $this->assertSame(
            array_fill(0, count($component->headerRow), null),
            $component->field_map,
        );
    }

    public function testQuantityAliasesMapToTheImporterQuantityField(): void
    {
        $component = Livewire::actingAs(User::factory()->canImport()->create())
            ->test(Importer::class)
            ->assertStatus(200)
            ->instance();

        foreach (['QTY', 'Qty', 'Quantity'] as $header) {
            $component->headerRow = [$header];
            $component->field_map = [];
            $component->updatingTypeOfImport('component');

            $this->assertSame(['quantity'], $component->field_map);
        }
    }

    public function testImporterOnlyListsAndSelectsTheCurrentUsersFiles(): void
    {
        $owner = User::factory()->canImport()->create();
        $otherUser = User::factory()->canImport()->create();
        $ownedImport = Import::factory()->create([
            'created_by' => $owner->id,
            'name' => 'Owned import',
        ]);
        $otherImport = Import::factory()->create([
            'created_by' => $otherUser->id,
            'name' => 'Private import',
        ]);

        $component = Livewire::actingAs($owner)->test(Importer::class);

        $this->assertSame(
            [$ownedImport->id],
            $component->instance()->files->pluck('id')->all(),
        );

        $component
            ->call('selectFile', $otherImport->id)
            ->assertSet('message_type', 'danger');
    }

    public function testImporterCannotDeleteAnotherUsersFile(): void
    {
        Storage::fake(config('filesystems.default'));

        $owner = User::factory()->canImport()->create();
        $otherUser = User::factory()->canImport()->create();
        $otherImport = Import::factory()->create([
            'created_by' => $otherUser->id,
            'file_path' => 'private.csv',
        ]);
        Storage::put('private_uploads/imports/private.csv', 'serial');

        Livewire::actingAs($owner)
            ->test(Importer::class)
            ->call('destroy', $otherImport->id)
            ->assertSet('message_type', 'danger');

        $this->assertDatabaseHas('imports', ['id' => $otherImport->id]);
        Storage::assertExists('private_uploads/imports/private.csv');
    }

    public function testDemoModePreventsDeletingAnImport(): void
    {
        Storage::fake(config('filesystems.default'));
        config(['app.lock_passwords' => true]);

        $owner = User::factory()->canImport()->create();
        $import = Import::factory()->create([
            'created_by' => $owner->id,
            'file_path' => 'demo-mode.csv',
        ]);
        Storage::put('private_uploads/imports/demo-mode.csv', 'serial');

        Livewire::actingAs($owner)
            ->test(Importer::class)
            ->call('destroy', $import->id)
            ->assertSet('message', trans('general.feature_disabled'))
            ->assertSet('message_type', 'danger');

        $this->assertDatabaseHas('imports', ['id' => $import->id]);
        Storage::assertExists('private_uploads/imports/demo-mode.csv');
    }
}
