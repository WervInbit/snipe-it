<?php

namespace Tests\Feature\Components\Domain;

use App\Models\Asset;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use App\Models\User;
use App\Services\ComponentLifecycleService;
use InvalidArgumentException;
use Tests\TestCase;

class ComponentLifecycleConditionSplitTest extends TestCase
{
    public function testInstalledComponentCanBeMarkedDamagedWithoutDetaching(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $component = ComponentInstance::factory()->installed($asset->id)->create();

        app(ComponentLifecycleService::class)->markDefective($component, [
            'performed_by' => $actor,
            'note' => 'Cracked shell.',
        ]);

        $component->refresh();

        $this->assertSame(ComponentInstance::STATUS_INSTALLED, $component->status);
        $this->assertSame(ComponentInstance::LIFECYCLE_ATTACHED, $component->lifecycle_status);
        $this->assertSame(ComponentInstance::CONDITION_STATUS_DAMAGED, $component->condition_status);
        $this->assertSame($asset->id, $component->current_asset_id);
        $this->assertDatabaseHas('component_events', [
            'component_instance_id' => $component->id,
            'event_type' => 'marked_defective',
            'from_status' => ComponentInstance::STATUS_INSTALLED,
            'to_status' => ComponentInstance::STATUS_INSTALLED,
        ]);
    }

    public function testInstalledComponentCanNeedAttentionWithoutDetaching(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $component = ComponentInstance::factory()->installed($asset->id)->create();

        app(ComponentLifecycleService::class)->flagNeedsVerification($component, [
            'performed_by' => $actor,
            'note' => 'Needs a bench check.',
        ]);

        $component->refresh();

        $this->assertSame(ComponentInstance::STATUS_INSTALLED, $component->status);
        $this->assertSame(ComponentInstance::LIFECYCLE_ATTACHED, $component->lifecycle_status);
        $this->assertSame(ComponentInstance::CONDITION_STATUS_NEEDS_ATTENTION, $component->condition_status);
        $this->assertSame($asset->id, $component->current_asset_id);
        $this->assertNull($component->storage_location_id);
        $this->assertNotNull($component->needs_verification_at);
    }

    public function testRemovedComponentKeepsPlacementWhenMarkedDamaged(): void
    {
        $actor = User::factory()->superuser()->create();
        $component = ComponentInstance::factory()->inTray($actor)->create();

        app(ComponentLifecycleService::class)->markDefective($component, [
            'performed_by' => $actor,
        ]);

        $component->refresh();

        $this->assertSame(ComponentInstance::STATUS_IN_TRANSFER, $component->status);
        $this->assertSame(ComponentInstance::LIFECYCLE_IN_TRAY, $component->lifecycle_status);
        $this->assertSame(ComponentInstance::CONDITION_STATUS_DAMAGED, $component->condition_status);
        $this->assertSame($actor->id, $component->held_by_user_id);
    }

    public function testDamagedStockComponentCanStillBeInstalledAfterConfirmation(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $stock = ComponentStorageLocation::factory()->stock()->create();
        $component = ComponentInstance::factory()->create([
            'storage_location_id' => $stock->id,
        ]);

        $service = app(ComponentLifecycleService::class);
        $service->markDefective($component, [
            'performed_by' => $actor,
        ]);
        $service->installIntoAsset($component, $asset, [
            'performed_by' => $actor,
            'condition_warning_confirmed' => true,
        ]);

        $component->refresh();

        $this->assertSame(ComponentInstance::STATUS_INSTALLED, $component->status);
        $this->assertSame(ComponentInstance::LIFECYCLE_ATTACHED, $component->lifecycle_status);
        $this->assertSame(ComponentInstance::CONDITION_STATUS_DAMAGED, $component->condition_status);
        $this->assertSame($asset->id, $component->current_asset_id);
    }

    public function testDestroyedComponentCannotBeInstalled(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $component = ComponentInstance::factory()->create();
        $service = app(ComponentLifecycleService::class);

        $service->markDestructionPending($component, null, [
            'performed_by' => $actor,
        ]);
        $service->markDestroyed($component, [
            'performed_by' => $actor,
            'note' => 'Destroyed with verification.',
        ]);

        $this->expectException(InvalidArgumentException::class);

        $service->installIntoAsset($component->fresh(), $asset, [
            'performed_by' => $actor,
            'condition_warning_confirmed' => true,
        ]);
    }

    public function testComponentMustBeDestructionPendingBeforeItCanBeDestroyed(): void
    {
        $actor = User::factory()->superuser()->create();
        $component = ComponentInstance::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Component must be marked destruction pending before it can be destroyed.');

        app(ComponentLifecycleService::class)->markDestroyed($component, [
            'performed_by' => $actor,
            'note' => 'Destroyed with verification.',
        ]);
    }

    public function testDestroyedComponentRequiresNoteOrEvidence(): void
    {
        $actor = User::factory()->superuser()->create();
        $component = ComponentInstance::factory()->create();
        $service = app(ComponentLifecycleService::class);

        $service->markDestructionPending($component, null, [
            'performed_by' => $actor,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A destruction note or verification evidence is required before marking a component destroyed.');

        $service->markDestroyed($component->fresh(), [
            'performed_by' => $actor,
        ]);
    }

    public function testLegacyStatusValuesMapToIndependentLifecycleAndCondition(): void
    {
        $needsAttention = ComponentInstance::factory()->create([
            'status' => ComponentInstance::STATUS_NEEDS_VERIFICATION,
            'lifecycle_status' => null,
            'condition_code' => ComponentInstance::CONDITION_UNKNOWN,
            'condition_status' => null,
        ]);
        $damaged = ComponentInstance::factory()->create([
            'status' => ComponentInstance::STATUS_DEFECTIVE,
            'lifecycle_status' => null,
            'condition_code' => ComponentInstance::CONDITION_BROKEN,
            'condition_status' => null,
        ]);

        $this->assertSame(ComponentInstance::LIFECYCLE_IN_STOCK, $needsAttention->lifecycle_status);
        $this->assertSame(ComponentInstance::CONDITION_STATUS_NEEDS_ATTENTION, $needsAttention->condition_status);
        $this->assertSame(ComponentInstance::LIFECYCLE_IN_STOCK, $damaged->lifecycle_status);
        $this->assertSame(ComponentInstance::CONDITION_STATUS_DAMAGED, $damaged->condition_status);
    }

    public function testMissingAndUnknownConditionCodesNormalizeToNeedsAttention(): void
    {
        $missingCondition = ComponentInstance::factory()->create([
            'condition_code' => null,
            'condition_status' => null,
        ]);
        $unknownCondition = ComponentInstance::factory()->create([
            'condition_code' => ComponentInstance::CONDITION_UNKNOWN,
            'condition_status' => null,
        ]);

        $this->assertSame(ComponentInstance::CONDITION_UNKNOWN, $missingCondition->condition_code);
        $this->assertSame(ComponentInstance::CONDITION_STATUS_NEEDS_ATTENTION, $missingCondition->condition_status);
        $this->assertSame(ComponentInstance::CONDITION_STATUS_NEEDS_ATTENTION, $unknownCondition->condition_status);
    }

    public function testNewLifecycleAndConditionFieldsPopulateLegacyCompatibilityFields(): void
    {
        $actor = User::factory()->superuser()->create();
        $component = app(ComponentLifecycleService::class)->createInstance([
            'display_name' => 'Damaged spare',
            'lifecycle_status' => ComponentInstance::LIFECYCLE_IN_STOCK,
            'condition_status' => ComponentInstance::CONDITION_STATUS_DAMAGED,
            'source_type' => ComponentInstance::SOURCE_MANUAL,
        ], $actor);

        $this->assertSame(ComponentInstance::STATUS_IN_STOCK, $component->status);
        $this->assertSame(ComponentInstance::LIFECYCLE_IN_STOCK, $component->lifecycle_status);
        $this->assertSame(ComponentInstance::CONDITION_BROKEN, $component->condition_code);
        $this->assertSame(ComponentInstance::CONDITION_STATUS_DAMAGED, $component->condition_status);
    }
}
