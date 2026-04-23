<?php

namespace Tests\Feature\Components\Ui;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Asset;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use App\Models\User;
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
        $this->assertSame(ComponentInstance::STATUS_NEEDS_VERIFICATION, $component->status);
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
            ->assertSessionHas('success', 'Component marked defective.');

        $component->refresh();
        $this->assertSame(ComponentInstance::STATUS_DEFECTIVE, $component->status);
        $this->assertNull($component->current_asset_id);
        $this->assertNull($component->held_by_user_id);

        $this->actingAs($user)
            ->get(route('components.show', $component))
            ->assertOk()
            ->assertSeeText('Defective');
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
