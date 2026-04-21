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
                'installed_as' => 'SSD Bay 1',
                'note' => 'Installed on target asset',
            ])
            ->assertSessionHas('success');

        $component->refresh();
        $this->assertSame(ComponentInstance::STATUS_INSTALLED, $component->status);
        $this->assertSame($targetAsset->id, $component->current_asset_id);
        $this->assertSame('SSD Bay 1', $component->installed_as);

        $this->actingAs($user)
            ->get(route('hardware.show', $sourceAsset))
            ->assertOk()
            ->assertSeeText('removed to tray');

        $this->actingAs($user)
            ->get(route('hardware.show', $targetAsset))
            ->assertOk()
            ->assertSeeText('installed');
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
}
