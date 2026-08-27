<?php

namespace Tests\Feature\Components\Ui;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Asset;
use App\Models\ComponentEvent;
use App\Models\ComponentDefinition;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use App\Models\ModelNumber;
use App\Models\ModelNumberComponentTemplate;
use App\Models\User;
use App\Presenters\ComponentPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComponentBrowserWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function testComponentIndexRendersTheSourceColumnWithoutLeakingATranslationKey(): void
    {
        $user = User::factory()->superuser()->create();

        $this->actingAs($user)
            ->get(route('components.index'))
            ->assertOk()
            ->assertDontSeeText('general.source');

        $sourceColumn = collect(json_decode(ComponentPresenter::dataTableLayout(), true))
            ->firstWhere('field', 'source_asset');

        $this->assertSame(trans('general.source'), $sourceColumn['title'] ?? null);
        $this->assertNotSame('general.source', $sourceColumn['title'] ?? null);
    }

    public function testManualCreateFormCreatesVisibleLooseComponent(): void
    {
        $user = User::factory()->superuser()->create();
        $stock = ComponentStorageLocation::factory()->stock()->create(['name' => 'Bench Stock']);

        $response = $this->actingAs($user)->post(route('components.store'), [
            'display_name' => 'Tracked SSD',
            'source_type' => ComponentInstance::SOURCE_EXTERNAL_INTAKE,
            'condition_code' => ComponentInstance::CONDITION_GOOD,
            'storage_location_id' => $stock->id,
            'notes' => 'Inbound intake',
        ]);

        $component = ComponentInstance::query()->latest('id')->first();

        $response->assertRedirect(route('components.show', $component));

        $this->actingAs($user)
            ->get(route('components.show', $component))
            ->assertOk()
            ->assertSeeText('Tracked SSD')
            ->assertSeeText('Bench Stock');
    }

    public function testInstalledComponentCanBeRemovedToTrayAndInstalledIntoAnotherAsset(): void
    {
        $user = User::factory()->superuser()->create();
        $sourceAsset = Asset::factory()->create();
        $targetAsset = Asset::factory()->create();
        $component = ComponentInstance::factory()->installed($sourceAsset->id)->create([
            'source_asset_id' => $sourceAsset->id,
            'display_name' => 'Browser Workflow Part',
        ]);

        $this->actingAs($user)
            ->post(route('components.remove_to_tray', $component), [
                'note' => 'Removed in browser workflow test',
            ])
            ->assertRedirect(route('components.show', $component))
            ->assertSessionHas('success');

        $component->refresh();
        $this->assertSame(ComponentInstance::STATUS_IN_TRANSFER, $component->status);
        $this->assertSame($user->id, $component->held_by_user_id);

        $this->actingAs($user)
            ->post(route('hardware.components.install-tray', $targetAsset), [
                'component_id' => $component->id,
                'note' => 'Installed on target asset',
            ])
            ->assertSessionHas('success');

        $component->refresh();
        $this->assertSame(ComponentInstance::STATUS_INSTALLED, $component->status);
        $this->assertSame($targetAsset->id, $component->current_asset_id);
        $this->assertNull($component->installed_as);

        $this->actingAs($user)
            ->get(route('hardware.show', $sourceAsset))
            ->assertOk()
            ->assertSeeText('Removed To Tray');

        $this->actingAs($user)
            ->get(route('hardware.show', $targetAsset))
            ->assertOk()
            ->assertSeeText('Installed');
    }

    public function testRemoveToTrayCanCaptureMissingSerialWhenUnlocked(): void
    {
        $user = User::factory()->superuser()->create();
        $sourceAsset = Asset::factory()->create();
        $component = ComponentInstance::factory()->installed($sourceAsset->id)->create([
            'serial' => null,
        ]);

        $this->actingAs($user)
            ->post(route('components.remove_to_tray', $component), [
                'serial_change_enabled' => 1,
                'serial' => 'SSD-SN-001',
                'note' => 'Serial captured while removing.',
            ])
            ->assertRedirect(route('components.show', $component))
            ->assertSessionHas('success');

        $component->refresh();
        $this->assertSame('SSD-SN-001', $component->serial);
        $this->assertSame(ComponentInstance::STATUS_IN_TRANSFER, $component->status);

        $event = ComponentEvent::query()
            ->where('component_instance_id', $component->id)
            ->where('event_type', 'removed_to_tray')
            ->firstOrFail();

        $this->assertTrue((bool) data_get($event->payload_json, 'serial_changed'));
        $this->assertNull(data_get($event->payload_json, 'previous_serial'));
        $this->assertSame('SSD-SN-001', data_get($event->payload_json, 'new_serial'));
    }

    public function testRemoveToTrayRequiresConfirmationBeforeChangingExistingSerial(): void
    {
        $user = User::factory()->superuser()->create();
        $sourceAsset = Asset::factory()->create();
        $component = ComponentInstance::factory()->installed($sourceAsset->id)->create([
            'serial' => 'OLD-SN-001',
        ]);

        $this->actingAs($user)
            ->post(route('components.remove_to_tray', $component), [
                'serial_change_enabled' => 1,
                'serial' => 'NEW-SN-001',
                'note' => 'Attempt without confirmation.',
            ])
            ->assertSessionHasErrors('serial');

        $component->refresh();
        $this->assertSame('OLD-SN-001', $component->serial);
        $this->assertSame(ComponentInstance::STATUS_INSTALLED, $component->status);

        $this->actingAs($user)
            ->post(route('components.remove_to_tray', $component), [
                'serial_change_enabled' => 1,
                'serial_change_confirmed' => 1,
                'serial' => 'NEW-SN-001',
                'note' => 'Confirmed serial correction.',
            ])
            ->assertRedirect(route('components.show', $component))
            ->assertSessionHas('success');

        $component->refresh();
        $this->assertSame('NEW-SN-001', $component->serial);
        $this->assertSame(ComponentInstance::STATUS_IN_TRANSFER, $component->status);

        $event = ComponentEvent::query()
            ->where('component_instance_id', $component->id)
            ->where('event_type', 'removed_to_tray')
            ->firstOrFail();

        $this->assertTrue((bool) data_get($event->payload_json, 'serial_changed'));
        $this->assertSame('OLD-SN-001', data_get($event->payload_json, 'previous_serial'));
        $this->assertSame('NEW-SN-001', data_get($event->payload_json, 'new_serial'));
    }

    public function testExpectedComponentToTrayCanCaptureSerial(): void
    {
        $user = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $modelNumber = ModelNumber::factory()->create();
        $asset->forceFill(['model_number_id' => $modelNumber->id])->save();
        $definition = ComponentDefinition::factory()->create(['name' => 'Expected SSD']);
        $template = ModelNumberComponentTemplate::factory()->create([
            'model_number_id' => $modelNumber->id,
            'component_definition_id' => $definition->id,
            'expected_name' => 'Expected SSD',
        ]);

        $response = $this->actingAs($user)
            ->post(route('hardware.components.expected.tray', [$asset, $template]), [
                'serial_change_enabled' => 1,
                'serial' => 'EXPECTED-SN-001',
                'note' => 'Removed expected component with serial.',
            ])
            ->assertSessionHas('success');

        $component = ComponentInstance::query()
            ->where('source_type', ComponentInstance::SOURCE_EXPECTED_BASELINE)
            ->where('source_asset_id', $asset->id)
            ->firstOrFail();

        $response->assertRedirect(route('components.show', $component));
        $this->assertSame('EXPECTED-SN-001', $component->serial);

        $event = ComponentEvent::query()
            ->where('component_instance_id', $component->id)
            ->where('event_type', 'removed_to_tray')
            ->firstOrFail();

        $this->assertTrue((bool) data_get($event->payload_json, 'serial_captured_on_removal'));
    }

    public function testAssetAddPageCanCreateDefinitionBackedComponentWithoutCustomName(): void
    {
        $user = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $definition = ComponentDefinition::factory()->create([
            'name' => 'Webcam',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('hardware.components.register', $asset), [
                'creation_mode' => 'definition',
                'component_definition_id' => $definition->id,
                'serial' => 'CAM-123',
                'condition_code' => ComponentInstance::CONDITION_GOOD,
                'note' => 'Installed from asset add form.',
            ])
            ->assertRedirect(route('hardware.show', $asset))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', trans('general.component_created_and_installed'));

        $this->assertDatabaseHas('component_instances', [
            'component_definition_id' => $definition->id,
            'current_asset_id' => $asset->id,
            'display_name' => 'Webcam',
            'serial' => 'CAM-123',
            'status' => ComponentInstance::STATUS_INSTALLED,
        ]);
    }

    public function testAssetInstallRequiresWarningConfirmationForDamagedComponent(): void
    {
        $user = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $component = ComponentInstance::factory()->create([
            'display_name' => 'Damaged Fan',
            'condition_code' => ComponentInstance::CONDITION_BROKEN,
            'condition_status' => ComponentInstance::CONDITION_STATUS_DAMAGED,
        ]);

        $this->actingAs($user)
            ->post(route('hardware.components.install', $asset), [
                'component_id' => $component->id,
            ])
            ->assertSessionHas('warning');

        $component->refresh();
        $this->assertNull($component->current_asset_id);
        $this->assertSame(ComponentInstance::LIFECYCLE_IN_STOCK, $component->lifecycle_status);

        $this->actingAs($user)
            ->post(route('hardware.components.install', $asset), [
                'component_id' => $component->id,
                'condition_warning_confirmed' => 1,
            ])
            ->assertRedirect(route('hardware.show', $asset))
            ->assertSessionHas('success', trans('general.component_installed'));

        $component->refresh();
        $this->assertSame($asset->id, $component->current_asset_id);
        $this->assertSame(ComponentInstance::CONDITION_STATUS_DAMAGED, $component->condition_status);
    }

    public function testAssetInstallAllowsSoldReturnedComponentAfterLifecycleWarningConfirmation(): void
    {
        $user = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $component = ComponentInstance::factory()->create([
            'display_name' => 'Sold Returned Adapter',
            'status' => ComponentInstance::STATUS_SOLD_RETURNED,
            'lifecycle_status' => ComponentInstance::LIFECYCLE_SOLD_RETURNED,
            'storage_location_id' => null,
            'current_asset_id' => null,
        ]);

        $this->actingAs($user)
            ->post(route('hardware.components.install', $asset), [
                'component_id' => $component->id,
            ])
            ->assertSessionHas('warning');

        $component->refresh();
        $this->assertNull($component->current_asset_id);
        $this->assertSame(ComponentInstance::LIFECYCLE_SOLD_RETURNED, $component->lifecycle_status);

        $this->actingAs($user)
            ->post(route('hardware.components.install', $asset), [
                'component_id' => $component->id,
                'lifecycle_warning_confirmed' => 1,
            ])
            ->assertRedirect(route('hardware.show', $asset))
            ->assertSessionHas('success', trans('general.component_installed'));

        $component->refresh();
        $this->assertSame($asset->id, $component->current_asset_id);
        $this->assertSame(ComponentInstance::LIFECYCLE_ATTACHED, $component->lifecycle_status);
    }

    public function testLooseComponentCanMoveThroughStockVerificationAndDestructionStates(): void
    {
        $user = User::factory()->superuser()->create();
        $stock = ComponentStorageLocation::factory()->stock()->create(['name' => 'Rack Stock']);
        $verification = ComponentStorageLocation::factory()->verification()->create(['name' => 'Verify Shelf']);
        $destruction = ComponentStorageLocation::factory()->destruction()->create(['name' => 'Destruction Bin']);
        $component = ComponentInstance::factory()->inTray($user)->create();

        $this->actingAs($user)
            ->post(route('components.move_to_stock', $component), [
                'storage_location_id' => $stock->id,
                'needs_verification' => 1,
                'verification_location_id' => $verification->id,
                'note' => 'Move to stock then verify',
            ])
            ->assertSessionHas('success');

        $component->refresh();
        $this->assertSame(ComponentInstance::STATUS_IN_STOCK, $component->status);
        $this->assertSame(ComponentInstance::LIFECYCLE_IN_STOCK, $component->lifecycle_status);
        $this->assertSame(ComponentInstance::CONDITION_STATUS_NEEDS_ATTENTION, $component->condition_status);
        $this->assertSame($verification->id, $component->storage_location_id);

        $this->actingAs($user)
            ->post(route('components.confirm_verification', $component), [
                'storage_location_id' => $stock->id,
                'note' => 'Verified and shelved',
            ])
            ->assertSessionHas('success');

        $component->refresh();
        $this->assertSame(ComponentInstance::STATUS_IN_STOCK, $component->status);
        $this->assertSame($stock->id, $component->storage_location_id);

        $this->actingAs($user)
            ->post(route('components.mark_destruction_pending', $component), [
                'storage_location_id' => $destruction->id,
                'note' => 'Retire this part',
            ])
            ->assertSessionHas('success');

        $component->refresh();
        $this->assertSame(ComponentInstance::STATUS_DESTRUCTION_PENDING, $component->status);
        $this->assertSame($destruction->id, $component->storage_location_id);

        $this->actingAs($user)
            ->post(route('components.mark_destroyed', $component), [
                'note' => 'Destroyed',
            ])
            ->assertSessionHas('success');

        $component->refresh();
        $this->assertSame(ComponentInstance::STATUS_DESTROYED_RECYCLED, $component->status);
    }

    public function testLooseComponentCanMoveToStockWithoutLocationAndSetLocationLater(): void
    {
        $user = User::factory()->superuser()->create();
        $stock = ComponentStorageLocation::factory()->stock()->create(['name' => 'Late Shelf']);
        $component = ComponentInstance::factory()->inTray($user)->create();

        $this->actingAs($user)
            ->post(route('components.move_to_stock', $component), [
                'note' => 'Return this to stock first',
            ])
            ->assertSessionHas('success');

        $component->refresh();
        $this->assertSame(ComponentInstance::STATUS_IN_STOCK, $component->status);
        $this->assertNull($component->storage_location_id);

        $this->actingAs($user)
            ->put(route('components.update', $component), [
                'storage_location_id' => $stock->id,
                'storage_location_note' => 'Shelved afterwards',
            ])
            ->assertRedirect(route('components.show', $component))
            ->assertSessionHas('success', 'Component storage location updated.');

        $component->refresh();
        $this->assertSame($stock->id, $component->storage_location_id);

        $this->actingAs($user)
            ->get(route('components.show', $component))
            ->assertOk()
            ->assertSeeText('Late Shelf');
    }

    public function testLooseComponentCanBeMarkedDefective(): void
    {
        $user = User::factory()->superuser()->create();
        $component = ComponentInstance::factory()->inTray($user)->create();

        $this->actingAs($user)
            ->post(route('components.mark_defective', $component), [
                'note' => 'Failed inspection',
            ])
            ->assertSessionHas('success', trans('general.component_marked_damaged'));

        $component->refresh();
        $this->assertSame(ComponentInstance::STATUS_IN_TRANSFER, $component->status);
        $this->assertSame(ComponentInstance::LIFECYCLE_IN_TRAY, $component->lifecycle_status);
        $this->assertSame(ComponentInstance::CONDITION_STATUS_DAMAGED, $component->condition_status);
        $this->assertNull($component->current_asset_id);
        $this->assertSame($user->id, $component->held_by_user_id);

        $this->actingAs($user)
            ->get(route('components.show', $component))
            ->assertOk()
            ->assertSeeText('Damaged');
    }

    public function testWebTrayInstallRejectsComponentsHeldByAnotherUser(): void
    {
        $holder = User::factory()->superuser()->create();
        $installer = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $component = ComponentInstance::factory()->inTray($holder)->create();

        $this->actingAs($installer)
            ->post(route('hardware.components.install-tray', $asset), [
                'component_id' => $component->id,
            ])
            ->assertSessionHas('error');

        $component->refresh();
        $this->assertSame(ComponentInstance::STATUS_IN_TRANSFER, $component->status);
        $this->assertNull($component->current_asset_id);
        $this->assertSame($holder->id, $component->held_by_user_id);
    }
}
