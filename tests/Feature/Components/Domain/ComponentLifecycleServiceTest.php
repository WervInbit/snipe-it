<?php

namespace Tests\Feature\Components\Domain;

use App\Models\Asset;
use App\Models\ComponentDefinition;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use App\Models\User;
use App\Services\ComponentLifecycleService;
use App\Services\ComponentTagGenerator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ComponentLifecycleServiceTest extends TestCase
{
    public function testCreatesComponentInstanceWithTagAndCreateEvent(): void
    {
        $actor = User::factory()->superuser()->create();
        $definition = ComponentDefinition::factory()->create([
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
        $location = ComponentStorageLocation::factory()->stock()->create();

        $instance = app(ComponentLifecycleService::class)->createInstance([
            'component_definition_id' => $definition->id,
            'display_name' => '16GB DDR4 SODIMM',
            'status' => ComponentInstance::STATUS_IN_STOCK,
            'storage_location_id' => $location->id,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ], $actor);

        $this->assertMatchesRegularExpression('/^INBIT-[A-Z]{2}\d{4}$/', $instance->component_tag);
        $this->assertNotEmpty($instance->qr_uid);
        $this->assertDatabaseHas('component_events', [
            'component_instance_id' => $instance->id,
            'event_type' => 'created',
            'to_status' => ComponentInstance::STATUS_IN_STOCK,
            'to_storage_location_id' => $location->id,
        ]);
    }

    public function testGeneratedComponentTagsSkipExistingAssetTags(): void
    {
        $actor = User::factory()->superuser()->create();
        $definition = ComponentDefinition::factory()->create([
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
        $location = ComponentStorageLocation::factory()->stock()->create();
        $asset = Asset::factory()->create(['asset_tag' => 'INBIT-AA0001']);

        app()->instance(ComponentTagGenerator::class, new class extends ComponentTagGenerator
        {
            private array $candidates = ['INBIT-AA0001', 'INBIT-BB0002'];

            protected function nextCandidate(): string
            {
                return array_shift($this->candidates);
            }
        });

        $instance = app(ComponentLifecycleService::class)->createInstance([
            'component_definition_id' => $definition->id,
            'display_name' => '256GB NVMe SSD',
            'status' => ComponentInstance::STATUS_IN_STOCK,
            'storage_location_id' => $location->id,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ], $actor);

        $this->assertSame('INBIT-BB0002', $instance->component_tag);
        $this->assertNotSame($asset->asset_tag, $instance->component_tag);
    }

    public function testRemovesInstalledComponentToTrayAndInstallsIntoTargetAsset(): void
    {
        $actor = User::factory()->superuser()->create();
        $sourceAsset = Asset::factory()->create();
        $targetAsset = Asset::factory()->create();

        $instance = ComponentInstance::factory()->installed($sourceAsset->id)->create([
            'source_asset_id' => $sourceAsset->id,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        $service = app(ComponentLifecycleService::class);
        $service->removeToTray($instance, $actor, [
            'note' => 'Removed during teardown.',
        ]);

        $instance->refresh();
        $this->assertSame(ComponentInstance::STATUS_IN_TRANSFER, $instance->status);
        $this->assertNull($instance->current_asset_id);
        $this->assertSame($actor->id, $instance->held_by_user_id);

        $service->installIntoAsset($instance, $targetAsset, [
            'performed_by' => $actor,
            'installed_as' => 'RAM Slot 1',
            'note' => 'Installed into replacement chassis.',
        ]);

        $instance->refresh();
        $this->assertSame(ComponentInstance::STATUS_INSTALLED, $instance->status);
        $this->assertSame($targetAsset->id, $instance->current_asset_id);
        $this->assertSame('RAM Slot 1', $instance->installed_as);
        $this->assertNull($instance->held_by_user_id);

        $this->assertDatabaseHas('component_events', [
            'component_instance_id' => $instance->id,
            'event_type' => 'removed_to_tray',
            'from_asset_id' => $sourceAsset->id,
            'to_status' => ComponentInstance::STATUS_IN_TRANSFER,
        ]);

        $this->assertDatabaseHas('component_events', [
            'component_instance_id' => $instance->id,
            'event_type' => 'installed',
            'to_asset_id' => $targetAsset->id,
            'to_status' => ComponentInstance::STATUS_INSTALLED,
        ]);
    }

    public function testMovesToStockAndEscalatesToVerificationWhenTrayAgingExpires(): void
    {
        $actor = User::factory()->superuser()->create();
        $stock = ComponentStorageLocation::factory()->stock()->create();
        $verification = ComponentStorageLocation::factory()->verification()->create();

        $instance = ComponentInstance::factory()->inTray($actor)->create([
            'transfer_started_at' => Carbon::now()->subHours(25),
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        $service = app(ComponentLifecycleService::class);
        $service->moveToStock($instance, $stock, [
            'performed_by' => $actor,
            'needs_verification' => true,
            'storage_location' => $verification,
        ]);

        $instance->refresh();
        $this->assertSame(ComponentInstance::STATUS_NEEDS_VERIFICATION, $instance->status);
        $this->assertSame($verification->id, $instance->storage_location_id);

        $aged = ComponentInstance::factory()->inTray($actor)->create([
            'transfer_started_at' => Carbon::now()->subHours(26),
            'storage_location_id' => $verification->id,
        ]);

        Artisan::call('components:age-tray');

        $aged->refresh();
        $this->assertSame(ComponentInstance::STATUS_NEEDS_VERIFICATION, $aged->status);
        $this->assertNotNull($aged->needs_verification_at);
        $this->assertDatabaseHas('component_events', [
            'component_instance_id' => $aged->id,
            'event_type' => 'flagged_needs_verification',
            'to_status' => ComponentInstance::STATUS_NEEDS_VERIFICATION,
        ]);
    }
}
