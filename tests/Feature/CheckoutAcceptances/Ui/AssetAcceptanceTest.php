<?php

namespace Tests\Feature\CheckoutAcceptances\Ui;

use App\Events\CheckoutAccepted;
use App\Events\CheckoutDeclined;
use App\Models\Accessory;
use App\Models\Asset;
use App\Models\CheckoutAcceptance;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AssetAcceptanceTest extends TestCase
{
    public function testPendingAssetAcceptancesAreHiddenWhileNonAssetAcceptancesRemainActionable()
    {
        $user = User::factory()->create();
        $assetAcceptance = CheckoutAcceptance::factory()->pending()->create([
            'assigned_to_id' => $user->id,
        ]);
        $accessoryAcceptance = CheckoutAcceptance::factory()
            ->pending()
            ->for(Accessory::factory()->appleMouse(), 'checkoutable')
            ->create([
                'assigned_to_id' => $user->id,
            ]);

        $this->actingAs($user)
            ->get(route('account.accept'))
            ->assertOk()
            ->assertViewHas('acceptances', function ($acceptances) use ($assetAcceptance, $accessoryAcceptance) {
                return $acceptances->contains($accessoryAcceptance)
                    && !$acceptances->contains($assetAcceptance);
            });

        $this->actingAs($user)
            ->get(route('account.accept.item', $accessoryAcceptance))
            ->assertOk()
            ->assertViewIs('account.accept.create');
    }

    public function testNonAssetAcceptanceResponseRemainsSupported()
    {
        Event::fake([CheckoutAccepted::class, CheckoutDeclined::class]);
        Notification::fake();

        $acceptance = CheckoutAcceptance::factory()
            ->pending()
            ->for(Accessory::factory()->appleMouse(), 'checkoutable')
            ->create();

        $this->actingAs($acceptance->assignedTo)
            ->post(route('account.store-acceptance', $acceptance), [
                'asset_acceptance' => 'accepted',
                'note' => 'supported non-asset response',
            ])
            ->assertRedirectToRoute('account.accept')
            ->assertSessionHas('success');

        $acceptance->refresh();
        $this->assertNotSoftDeleted($acceptance);
        $this->assertNotNull($acceptance->accepted_at);
        Event::assertDispatched(CheckoutAccepted::class);
        Event::assertNotDispatched(CheckoutDeclined::class);
    }

    public function testOpeningPendingAssetAcceptanceRetiresStaleAssignmentWithoutLifecycleHistory()
    {
        Event::fake([CheckoutAccepted::class, CheckoutDeclined::class]);

        $acceptance = CheckoutAcceptance::factory()->pending()->create();
        $asset = $acceptance->checkoutable;

        $this->actingAs($acceptance->assignedTo)
            ->get(route('account.accept.item', $acceptance))
            ->assertRedirectToRoute('account.accept')
            ->assertSessionHas(
                'error',
                trans('admin/hardware/message.legacy_assignment_disabled')
            );

        $this->assertRetiredWithoutResponseHistory($acceptance, $asset);
    }

    #[DataProvider('assetAcceptanceResponses')]
    public function testPostingPendingAssetAcceptanceIsBlockedWithoutLifecycleHistory(string $response)
    {
        Event::fake([CheckoutAccepted::class, CheckoutDeclined::class]);

        $acceptance = CheckoutAcceptance::factory()->pending()->create();
        $asset = $acceptance->checkoutable;

        $this->actingAs($acceptance->assignedTo)
            ->post(route('account.store-acceptance', $acceptance), [
                'asset_acceptance' => $response,
                'note' => 'must not become lifecycle history',
            ])
            ->assertRedirectToRoute('account.accept')
            ->assertSessionHas(
                'error',
                trans('admin/hardware/message.legacy_assignment_disabled')
            );

        $this->assertRetiredWithoutResponseHistory($acceptance, $asset);
    }

    public static function assetAcceptanceResponses(): array
    {
        return [
            'accept' => ['accepted'],
            'decline' => ['declined'],
        ];
    }

    public function testAnotherUserCannotRetirePendingAssetAcceptance()
    {
        Event::fake([CheckoutAccepted::class, CheckoutDeclined::class]);

        $acceptance = CheckoutAcceptance::factory()->pending()->create();
        $asset = $acceptance->checkoutable;
        $assignedToBefore = $asset->assigned_to;

        $this->actingAs(User::factory()->create())
            ->post(route('account.store-acceptance', $acceptance), [
                'asset_acceptance' => 'accepted',
            ])
            ->assertRedirectToRoute('account.accept')
            ->assertSessionHas(
                'error',
                trans('admin/users/message.error.incorrect_user_accepted')
            );

        $this->assertTrue($acceptance->fresh()->isPending());
        $this->assertSame($assignedToBefore, $asset->fresh()->assigned_to);
        Event::assertNotDispatched(CheckoutAccepted::class);
        Event::assertNotDispatched(CheckoutDeclined::class);
    }

    public function testCompletedAssetAcceptanceRemainsReadOnlyHistory()
    {
        Event::fake([CheckoutAccepted::class, CheckoutDeclined::class]);

        $acceptance = CheckoutAcceptance::factory()->accepted()->create();
        $acceptedAt = $acceptance->accepted_at;

        $this->actingAs($acceptance->assignedTo)
            ->post(route('account.store-acceptance', $acceptance), [
                'asset_acceptance' => 'declined',
            ])
            ->assertRedirectToRoute('account.accept')
            ->assertSessionHas('error');

        $acceptance->refresh();
        $this->assertNotSoftDeleted($acceptance);
        $this->assertTrue($acceptedAt->equalTo($acceptance->accepted_at));
        $this->assertNull($acceptance->declined_at);
        Event::assertNotDispatched(CheckoutAccepted::class);
        Event::assertNotDispatched(CheckoutDeclined::class);
    }

    private function assertRetiredWithoutResponseHistory(
        CheckoutAcceptance $acceptance,
        Asset $asset
    ): void {
        $asset->refresh();

        $this->assertSoftDeleted($acceptance);
        $this->assertNull($asset->assigned_to);
        $this->assertNull($asset->assigned_type);
        $this->assertNull($asset->accepted);
        $this->assertNull($asset->expected_checkin);
        $this->assertDatabaseMissing('action_logs', [
            'item_type' => Asset::class,
            'item_id' => $asset->id,
            'action_type' => 'accepted',
        ]);
        $this->assertDatabaseMissing('action_logs', [
            'item_type' => Asset::class,
            'item_id' => $asset->id,
            'action_type' => 'declined',
        ]);
        $this->assertDatabaseMissing('action_logs', [
            'item_type' => Asset::class,
            'item_id' => $asset->id,
            'action_type' => 'checkin from',
        ]);
        Event::assertNotDispatched(CheckoutAccepted::class);
        Event::assertNotDispatched(CheckoutDeclined::class);
    }
}
