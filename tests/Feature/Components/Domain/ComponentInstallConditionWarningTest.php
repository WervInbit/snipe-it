<?php

namespace Tests\Feature\Components\Domain;

use App\Exceptions\ComponentConditionWarningException;
use App\Exceptions\ComponentLifecycleWarningException;
use App\Models\Asset;
use App\Models\ComponentInstance;
use App\Models\User;
use App\Services\ComponentLifecycleService;
use InvalidArgumentException;
use Tests\TestCase;

class ComponentInstallConditionWarningTest extends TestCase
{
    public function testDamagedComponentInstallRequiresConfirmationAndCanProceed(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $component = ComponentInstance::factory()->create();
        $service = app(ComponentLifecycleService::class);

        $service->markDefective($component, [
            'performed_by' => $actor,
            'note' => 'Cracked bracket.',
        ]);

        try {
            $service->installIntoAsset($component->fresh(), $asset, [
                'performed_by' => $actor,
            ]);
            $this->fail('Damaged components should require a condition warning confirmation before install.');
        } catch (ComponentConditionWarningException $exception) {
            $this->assertSame(ComponentInstance::CONDITION_STATUS_DAMAGED, $exception->conditionStatus);
        }

        $component->refresh();
        $this->assertNull($component->current_asset_id);
        $this->assertSame(ComponentInstance::LIFECYCLE_IN_STOCK, $component->lifecycle_status);

        $service->installIntoAsset($component->fresh(), $asset, [
            'performed_by' => $actor,
            'condition_warning_confirmed' => true,
        ]);

        $component->refresh();
        $this->assertSame(ComponentInstance::LIFECYCLE_ATTACHED, $component->lifecycle_status);
        $this->assertSame(ComponentInstance::CONDITION_STATUS_DAMAGED, $component->condition_status);
        $this->assertSame($asset->id, $component->current_asset_id);
    }

    public function testNeedsAttentionComponentInstallRequiresConfirmation(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $component = ComponentInstance::factory()->create([
            'condition_code' => ComponentInstance::CONDITION_UNKNOWN,
            'condition_status' => ComponentInstance::CONDITION_STATUS_NEEDS_ATTENTION,
        ]);

        try {
            app(ComponentLifecycleService::class)->installIntoAsset($component, $asset, [
                'performed_by' => $actor,
            ]);
            $this->fail('Needs-attention components should require a condition warning confirmation before install.');
        } catch (ComponentConditionWarningException $exception) {
            $this->assertSame(ComponentInstance::CONDITION_STATUS_NEEDS_ATTENTION, $exception->conditionStatus);
        }

        $component->refresh();
        $this->assertNull($component->current_asset_id);
        $this->assertSame(ComponentInstance::LIFECYCLE_IN_STOCK, $component->lifecycle_status);
    }

    public function testDestroyedComponentInstallStillFailsEvenWhenWarningConfirmed(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $component = ComponentInstance::factory()->create();
        $service = app(ComponentLifecycleService::class);

        $service->markDestructionPending($component, null, [
            'performed_by' => $actor,
        ]);
        $service->markDestroyed($component->fresh(), [
            'performed_by' => $actor,
            'note' => 'Destroyed with verification.',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Destroyed or destruction-pending components cannot be installed.');

        $service->installIntoAsset($component->fresh(), $asset, [
            'performed_by' => $actor,
            'condition_warning_confirmed' => true,
            'lifecycle_warning_confirmed' => true,
        ]);
    }

    public function testSoldReturnedComponentInstallRequiresLifecycleConfirmationAndCanProceed(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $component = ComponentInstance::factory()->create([
            'status' => ComponentInstance::STATUS_SOLD_RETURNED,
            'lifecycle_status' => ComponentInstance::LIFECYCLE_SOLD_RETURNED,
            'storage_location_id' => null,
            'current_asset_id' => null,
        ]);
        $service = app(ComponentLifecycleService::class);

        try {
            $service->installIntoAsset($component, $asset, [
                'performed_by' => $actor,
            ]);
            $this->fail('Sold / returned components should require a lifecycle warning confirmation before install.');
        } catch (ComponentLifecycleWarningException $exception) {
            $this->assertSame(ComponentInstance::LIFECYCLE_SOLD_RETURNED, $exception->lifecycleStatus);
        }

        $component->refresh();
        $this->assertNull($component->current_asset_id);
        $this->assertSame(ComponentInstance::LIFECYCLE_SOLD_RETURNED, $component->lifecycle_status);

        $service->installIntoAsset($component->fresh(), $asset, [
            'performed_by' => $actor,
            'lifecycle_warning_confirmed' => true,
        ]);

        $component->refresh();
        $this->assertSame(ComponentInstance::STATUS_INSTALLED, $component->status);
        $this->assertSame(ComponentInstance::LIFECYCLE_ATTACHED, $component->lifecycle_status);
        $this->assertSame($asset->id, $component->current_asset_id);
    }
}
