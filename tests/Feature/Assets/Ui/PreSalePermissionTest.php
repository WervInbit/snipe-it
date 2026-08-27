<?php

namespace Tests\Feature\Assets\Ui;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Asset;
use App\Models\Statuslabel;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class PreSalePermissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_sale_transition_permission_can_move_asset_to_deployable_status_without_asset_edit(): void
    {
        $readyForSale = $this->preSaleStatus();
        $asset = Asset::factory()->create([
            'status_id' => $this->pendingStatus()->id,
            'tests_completed_ok' => true,
            'is_sellable' => false,
        ]);
        $user = $this->saleTransitionUser();

        $this->actingAs($user)
            ->from(route('hardware.show', $asset))
            ->patch(route('hardware.status.update', $asset), [
                'status_id' => $readyForSale->id,
                'ack_failed_tests' => 1,
            ])
            ->assertRedirect(route('hardware.show', $asset));

        $asset->refresh();

        $this->assertSame($readyForSale->id, $asset->status_id);
        $this->assertFalse($asset->is_sellable);
    }

    public function test_sale_transition_permission_does_not_allow_quality_grade_edit(): void
    {
        $asset = Asset::factory()->create([
            'status_id' => $this->pendingStatus()->id,
            'quality_grade' => Asset::QUALITY_GRADE_B,
            'tests_completed_ok' => true,
        ]);

        $this->actingAs($this->saleTransitionUser())
            ->patch(route('hardware.status.update', $asset), [
                'status_id' => $this->preSaleStatus()->id,
                'quality_grade' => Asset::QUALITY_GRADE_A,
                'ack_failed_tests' => 1,
            ])
            ->assertForbidden();

        $this->assertSame(Asset::QUALITY_GRADE_B, $asset->fresh()->quality_grade);
    }

    public function test_sale_transition_permission_can_complete_sold_and_forces_unsellable(): void
    {
        $asset = Asset::factory()->create([
            'status_id' => $this->pendingStatus()->id,
            'tests_completed_ok' => true,
            'is_sellable' => true,
        ]);
        $sold = Statuslabel::factory()->archived()->create([
            'name' => 'Sold ' . Str::uuid(),
            'lifecycle_stage' => Statuslabel::LIFECYCLE_SOLD,
            'default_label' => 0,
        ]);

        $this->actingAs($this->saleTransitionUser())
            ->from(route('hardware.show', $asset))
            ->patch(route('hardware.status.update', $asset), [
                'status_id' => $sold->id,
                'ack_failed_tests' => 1,
            ])
            ->assertRedirect(route('hardware.show', $asset));

        $asset->refresh();

        $this->assertSame($sold->id, $asset->status_id);
        $this->assertFalse($asset->is_sellable);
        $this->assertSame(1, (int) $asset->archived);
    }

    public function test_user_without_sale_transition_permission_cannot_complete_sold(): void
    {
        $asset = Asset::factory()->create([
            'status_id' => $this->pendingStatus()->id,
            'tests_completed_ok' => true,
        ]);
        $sold = Statuslabel::factory()->archived()->create([
            'name' => 'Sold ' . Str::uuid(),
            'lifecycle_stage' => Statuslabel::LIFECYCLE_SOLD,
            'default_label' => 0,
        ]);

        $this->actingAs(User::factory()->viewAssets()->create())
            ->patch(route('hardware.status.update', $asset), [
                'status_id' => $sold->id,
            ])
            ->assertForbidden();

        $this->assertNotSame($sold->id, $asset->fresh()->status_id);
    }

    private function saleTransitionUser(): User
    {
        return User::factory()->create([
            'permissions' => json_encode([
                'assets.view' => '1',
                'assets.sale_transition' => '1',
            ]),
        ]);
    }

    private function preSaleStatus(): Statuslabel
    {
        return Statuslabel::factory()->rtd()->create([
            'name' => 'Pre-Sale ' . Str::uuid(),
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
