<?php

namespace Tests\Feature\Checkouts\Api;

use App\Mail\CheckoutConsumableMail;
use App\Models\Actionlog;
use App\Models\Company;
use App\Models\Consumable;
use App\Models\User;
use App\Notifications\CheckoutConsumableNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ConsumableCheckoutTest extends TestCase
{
    public function testCheckingOutConsumableRequiresCorrectPermission()
    {
        $this->actingAsForApi(User::factory()->create())
            ->postJson(route('api.consumables.checkout', Consumable::factory()->create()))
            ->assertForbidden();
    }

    public function testValidationWhenCheckingOutConsumable()
    {
        $this->actingAsForApi(User::factory()->checkoutConsumables()->create())
            ->postJson(route('api.consumables.checkout', Consumable::factory()->create()), [
                // missing assigned_to
            ])
            ->assertStatusMessageIs('error');
    }

    public function testConsumableMustBeAvailableWhenCheckingOut()
    {
        $this->actingAsForApi(User::factory()->checkoutConsumables()->create())
            ->postJson(route('api.consumables.checkout', Consumable::factory()->withoutItemsRemaining()->create()), [
                'assigned_to' => User::factory()->create()->id,
            ])
            ->assertStatusMessageIs('error');
    }

    public function testConsumableCanBeCheckedOut()
    {
        $consumable = Consumable::factory()->create();
        $user = User::factory()->create();

        $this->actingAsForApi(User::factory()->checkoutConsumables()->create())
            ->postJson(route('api.consumables.checkout', $consumable), [
                'assigned_to' => $user->id,
            ]);

        $this->assertTrue($user->consumables->contains($consumable));
        $this->assertHasTheseActionLogs($consumable, ['create', 'checkout']);
    }

    public function testUserSentNotificationUponCheckout()
    {
        Mail::fake();

        $consumable = Consumable::factory()->requiringAcceptance()->create();

        $user = User::factory()->create();

        $this->actingAsForApi(User::factory()->checkoutConsumables()->create())
            ->postJson(route('api.consumables.checkout', $consumable), [
                'assigned_to' => $user->id,
            ]);

        Mail::assertSent(CheckoutConsumableMail::class, function ($mail) use ($consumable, $user) {
            return $mail->hasTo($user->email);
        });
    }

    public function testActionLogCreatedUponCheckout()
    {$consumable = Consumable::factory()->create();
        $actor = User::factory()->checkoutConsumables()->create();
        $user = User::factory()->create();

        $this->actingAsForApi($actor)
            ->postJson(route('api.consumables.checkout', $consumable), [
                'assigned_to' => $user->id,
                'note' => 'oh hi there',
            ]);

        $this->assertEquals(
            1,
            Actionlog::where([
                'action_type' => 'checkout',
                'target_id' => $user->id,
                'target_type' => User::class,
                'item_id' => $consumable->id,
                'item_type' => Consumable::class,
                'created_by' => $actor->id,
                'note' => 'oh hi there',
            ])->count(),
            'Log entry either does not exist or there are more than expected'
        );
    }

    public function testCheckoutQuantityCreatesExactAssignmentsAttributedToOperator(): void
    {
        $consumable = Consumable::factory()->create(['qty' => 2]);
        $target = User::factory()->create();
        $actor = User::factory()->checkoutConsumables()->create();

        $this->actingAsForApi($actor)
            ->postJson(route('api.consumables.checkout', $consumable), [
                'assigned_to' => $target->id,
                'checkout_qty' => 2,
            ])
            ->assertStatusMessageIs('success');

        $this->assertDatabaseCount('consumables_users', 2);
        $this->assertSame(
            2,
            $consumable->consumableAssignments()
                ->where('assigned_to', $target->id)
                ->where('created_by', $actor->id)
                ->count()
        );
    }

    public function testInvalidCheckoutQuantityDoesNotCreateAssignment(): void
    {
        $consumable = Consumable::factory()->create(['qty' => 2]);

        $this->actingAsForApi(User::factory()->checkoutConsumables()->create())
            ->postJson(route('api.consumables.checkout', $consumable), [
                'assigned_to' => User::factory()->create()->id,
                'checkout_qty' => -1,
            ])
            ->assertStatusMessageIs('error');

        $this->assertDatabaseCount('consumables_users', 0);
    }

    public function testExhaustedInventoryCannotBeOverAllocated(): void
    {
        $target = User::factory()->create();
        $consumable = Consumable::factory()->create(['qty' => 1]);

        $consumable->users()->attach($target->id, [
            'created_by' => User::factory()->superuser()->create()->id,
        ]);

        $this->actingAsForApi(User::factory()->checkoutConsumables()->create())
            ->postJson(route('api.consumables.checkout', $consumable), [
                'assigned_to' => $target->id,
                'checkout_qty' => 1,
            ])
            ->assertStatusMessageIs('error');

        $this->assertSame(1, $consumable->consumableAssignments()->count());
        $this->assertSame(0, $consumable->fresh()->numRemaining());
    }

    public function testConsumableCannotBeCheckedOutAcrossCompanyBoundary(): void
    {
        $this->settings->enableMultipleFullCompanySupport();
        $itemCompany = Company::factory()->create();
        $targetCompany = Company::factory()->create();
        $consumable = Consumable::factory()->for($itemCompany)->create();
        $target = User::factory()->for($targetCompany)->create();

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.consumables.checkout', $consumable), [
                'assigned_to' => $target->id,
            ])
            ->assertStatusMessageIs('error')
            ->assertJsonPath('messages', trans('general.error_user_company'));

        $this->assertSame(0, $consumable->consumableAssignments()->count());
    }
}
