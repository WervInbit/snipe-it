<?php

namespace Tests\Feature\Assets\Ui;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Asset;
use App\Models\CheckoutAcceptance;
use App\Models\ComponentInstance;
use App\Models\Statuslabel;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class SellingStateComponentWarningTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function testDetailStatusChangeWarnsForAttachedDamagedComponentAndCanProceed(): void
    {
        $readyForSale = $this->readyForSaleStatus();
        $originalStatus = $this->pendingStatus();
        $asset = Asset::factory()->create([
            'status_id' => $originalStatus->id,
            'tests_completed_ok' => true,
        ]);
        ComponentInstance::factory()->installed($asset->id)->create([
            'display_name' => 'Cracked display assembly',
            'condition_code' => ComponentInstance::CONDITION_BROKEN,
            'condition_status' => ComponentInstance::CONDITION_STATUS_DAMAGED,
        ]);
        $user = User::factory()->superuser()->create();

        $this->actingAs($user)
            ->from(route('hardware.show', $asset))
            ->patch(route('hardware.status.update', $asset), [
                'status_id' => $readyForSale->id,
                'ack_failed_tests' => 1,
            ])
            ->assertRedirect(route('hardware.show', $asset))
            ->assertSessionHas('warning')
            ->assertSessionHas('requires_ack_component_issues');

        $this->assertTrue(
            collect(session('component_issue_details'))->contains(
                fn ($line) => str_contains($line, 'Cracked display assembly')
            )
        );
        $this->assertSame($originalStatus->id, $asset->fresh()->status_id);

        $this->actingAs($user)
            ->from(route('hardware.show', $asset))
            ->patch(route('hardware.status.update', $asset), [
                'status_id' => $readyForSale->id,
                'ack_failed_tests' => 1,
                'ack_component_issues' => 1,
            ])
            ->assertRedirect(route('hardware.show', $asset));

        $this->assertSame($readyForSale->id, $asset->fresh()->status_id);
    }

    public function testAssetEditStatusChangeWarnsForAttachedNeedsAttentionSubcomponent(): void
    {
        $readyForSale = $this->readyForSaleStatus();
        $originalStatus = $this->pendingStatus();
        $asset = Asset::factory()->create([
            'status_id' => $originalStatus->id,
            'tests_completed_ok' => true,
        ]);
        $parent = ComponentInstance::factory()->installed($asset->id)->create([
            'display_name' => 'Main board',
        ]);
        ComponentInstance::factory()->asChildOf($parent)->create([
            'display_name' => 'Unverified USB-C board',
            'condition_code' => ComponentInstance::CONDITION_UNKNOWN,
            'condition_status' => ComponentInstance::CONDITION_STATUS_NEEDS_ATTENTION,
        ]);
        $user = User::factory()->superuser()->create();

        $this->actingAs($user)
            ->from(route('hardware.edit', $asset))
            ->put(route('hardware.update', $asset), [
                'asset_tags' => $asset->asset_tag,
                'status_id' => $readyForSale->id,
                'model_id' => $asset->model_id,
                'model_number_id' => $asset->model_number_id,
                'redirect_option' => 'item',
                'ack_failed_tests' => 1,
            ])
            ->assertRedirect(route('hardware.edit', $asset))
            ->assertSessionHas('warning')
            ->assertSessionHas('requires_ack_component_issues');

        $this->assertTrue(
            collect(session('component_issue_details'))->contains(
                fn ($line) => str_contains($line, 'Unverified USB-C board')
            )
        );
        $this->assertSame($originalStatus->id, $asset->fresh()->status_id);

        $this->actingAs($user)
            ->from(route('hardware.edit', $asset))
            ->put(route('hardware.update', $asset), [
                'asset_tags' => $asset->asset_tag,
                'status_id' => $readyForSale->id,
                'model_id' => $asset->model_id,
                'model_number_id' => $asset->model_number_id,
                'redirect_option' => 'item',
                'ack_failed_tests' => 1,
                'ack_component_issues' => 1,
            ])
            ->assertRedirect(route('hardware.show', $asset));

        $this->assertSame($readyForSale->id, $asset->fresh()->status_id);
    }

    public function testDetachedDamagedComponentDoesNotWarnForSellingStatus(): void
    {
        $readyForSale = $this->readyForSaleStatus();
        $originalStatus = $this->pendingStatus();
        $asset = Asset::factory()->create([
            'status_id' => $originalStatus->id,
            'tests_completed_ok' => true,
        ]);
        ComponentInstance::factory()->create([
            'display_name' => 'Damaged loose spare',
            'condition_code' => ComponentInstance::CONDITION_BROKEN,
            'condition_status' => ComponentInstance::CONDITION_STATUS_DAMAGED,
            'current_asset_id' => null,
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->from(route('hardware.show', $asset))
            ->patch(route('hardware.status.update', $asset), [
                'status_id' => $readyForSale->id,
                'ack_failed_tests' => 1,
            ])
            ->assertRedirect(route('hardware.show', $asset))
            ->assertSessionMissing('requires_ack_component_issues');

        $this->assertSame($readyForSale->id, $asset->fresh()->status_id);
    }

    public function testSaleToggleWarnsForAttachedDamagedComponentAndCanProceed(): void
    {
        $asset = Asset::factory()->create(['is_sellable' => false]);
        ComponentInstance::factory()->installed($asset->id)->create([
            'display_name' => 'Damaged battery pack',
            'condition_code' => ComponentInstance::CONDITION_BROKEN,
            'condition_status' => ComponentInstance::CONDITION_STATUS_DAMAGED,
        ]);
        $user = User::factory()->superuser()->create();

        $this->actingAs($user)
            ->from(route('hardware.show', $asset))
            ->patch(route('hardware.toggle-sale', $asset), [
                'is_sellable' => 1,
            ])
            ->assertRedirect(route('hardware.show', $asset))
            ->assertSessionHas('warning')
            ->assertSessionHas('requires_ack_component_issues');

        $this->assertFalse($asset->fresh()->is_sellable);

        $this->actingAs($user)
            ->from(route('hardware.show', $asset))
            ->patch(route('hardware.toggle-sale', $asset), [
                'is_sellable' => 1,
                'ack_component_issues' => 1,
            ])
            ->assertRedirect(route('hardware.show', $asset));

        $this->assertTrue($asset->fresh()->is_sellable);
    }

    public function testBulkSellingStatusWarnsForAttachedComponentIssuesAndCanProceed(): void
    {
        $readyForSale = $this->readyForSaleStatus();
        $originalStatus = $this->pendingStatus();
        $assetWithIssue = Asset::factory()->create([
            'status_id' => $originalStatus->id,
            'tests_completed_ok' => true,
        ]);
        $cleanAsset = Asset::factory()->create([
            'status_id' => $originalStatus->id,
            'tests_completed_ok' => true,
        ]);
        ComponentInstance::factory()->installed($assetWithIssue->id)->create([
            'display_name' => 'Needs bench verification',
            'condition_code' => ComponentInstance::CONDITION_UNKNOWN,
            'condition_status' => ComponentInstance::CONDITION_STATUS_NEEDS_ATTENTION,
        ]);
        $user = User::factory()->superuser()->create();
        $payload = [
            'ids' => [$assetWithIssue->id, $cleanAsset->id],
            'status_id' => $readyForSale->id,
            'ack_failed_tests' => 1,
        ];

        $this->actingAs($user)
            ->from(route('hardware/bulkedit'))
            ->post(route('hardware/bulksave'), $payload)
            ->assertRedirect(route('hardware/bulkedit'))
            ->assertSessionHas('warning')
            ->assertSessionHas('requires_ack_component_issues');

        $this->assertSame($originalStatus->id, $assetWithIssue->fresh()->status_id);
        $this->assertSame($originalStatus->id, $cleanAsset->fresh()->status_id);

        $this->actingAs($user)
            ->from(route('hardware/bulkedit'))
            ->post(route('hardware/bulksave'), $payload + [
                'ack_component_issues' => 1,
            ])
            ->assertRedirect();

        $this->assertSame($readyForSale->id, $assetWithIssue->fresh()->status_id);
        $this->assertSame($readyForSale->id, $cleanAsset->fresh()->status_id);
    }

    public function testBulkNonDeployableStatusClearsRetiredAssignmentState(): void
    {
        $assignedUser = User::factory()->create();
        $asset = Asset::factory()->assignedToUser($assignedUser)->create([
            'status_id' => $this->pendingStatus()->id,
            'accepted' => 'pending',
            'expected_checkin' => now()->addDay(),
        ]);
        $acceptance = CheckoutAcceptance::factory()->pending()->create([
            'checkoutable_type' => Asset::class,
            'checkoutable_id' => $asset->id,
            'assigned_to_id' => $assignedUser->id,
        ]);
        $retired = Statuslabel::factory()->archived()->create([
            'name' => 'Bulk retired ' . Str::uuid(),
            'default_label' => 0,
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->from(route('hardware/bulkedit'))
            ->post(route('hardware/bulksave'), [
                'ids' => [$asset->id],
                'status_id' => $retired->id,
            ])
            ->assertRedirect();

        $asset->refresh();
        $this->assertSame($retired->id, $asset->status_id);
        $this->assertNull($asset->assigned_to);
        $this->assertNull($asset->assigned_type);
        $this->assertNull($asset->accepted);
        $this->assertNull($asset->expected_checkin);
        $this->assertSoftDeleted($acceptance);
    }

    private function readyForSaleStatus(): Statuslabel
    {
        return Statuslabel::factory()->rtd()->create([
            'name' => 'Ready for Sale ' . Str::uuid(),
            'lifecycle_stage' => Statuslabel::LIFECYCLE_READY_FOR_SALE,
            'default_label' => 0,
        ]);
    }

    private function pendingStatus(): Statuslabel
    {
        return Statuslabel::factory()->pending()->create([
            'name' => 'Pending ' . Str::uuid(),
            'default_label' => 0,
        ]);
    }
}
