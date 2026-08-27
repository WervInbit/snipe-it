<?php

namespace Tests\Feature\Users\Ui\BulkActions;

use App\Models\Accessory;
use App\Models\Asset;
use App\Models\Consumable;
use App\Models\CheckoutAcceptance;
use App\Models\Company;
use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class BulkDeleteUsersTest extends TestCase
{
    public function test_requires_correct_permission()
    {
        $this->actingAs(User::factory()->editUsers()->create())
            ->post(route('users/bulksave'), [
                'ids' => [
                    User::factory()->create()->id,
                ],
                'delete_user' => '1',
            ])
            ->assertForbidden();
    }

    public function test_validation()
    {
        $user = User::factory()->create();
        Asset::factory()->assignedToUser($user)->create();

        $actor = $this->actingAs(User::factory()->deleteUsers()->create());

        // "ids" required
        $actor->post(route('users/bulksave'), [
            'delete_user' => '1',
        ])->assertSessionHas('error')->assertRedirect();

        // The retired bulk check-in mode cannot be invoked without deleting users.
        $actor->post(route('users/bulksave'), [
            'ids' => [
                $user->id,
            ],
        ])->assertSessionHas('error')->assertRedirect();
    }

    public function test_cannot_perform_bulk_actions_on_self()
    {
        $actor = User::factory()->deleteUsers()->create();

        $this->actingAs($actor)
            ->post(route('users/bulksave'), [
                'ids' => [
                    $actor->id,
                ],
                'delete_user' => '1',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', trans('general.bulk_checkin_delete_success'));

        $this->assertNotSoftDeleted($actor);
    }

    public function test_accessories_can_be_bulk_checked_in()
    {
        [$accessoryA, $accessoryB] = Accessory::factory()->count(2)->create();
        [$userA, $userB, $userC] = User::factory()->count(3)->create();

        // Add checkouts for multiple accessories to multiple users to get different ids in the mix
        $this->attachAccessoryToUsers($accessoryA, [$userA, $userB, $userC]);
        $this->attachAccessoryToUsers($accessoryB, [$userA, $userB]);

        $this->actingAs(User::factory()->deleteUsers()->create())
            ->post(route('users/bulksave'), [
                'ids' => [
                    $userA->id,
                    $userC->id,
                ],
                'delete_user' => '1',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertTrue(User::withTrashed()->findOrFail($userA->id)->accessories->isEmpty());
        $this->assertTrue($userB->fresh()->accessories->isNotEmpty());
        $this->assertTrue(User::withTrashed()->findOrFail($userC->id)->accessories->isEmpty());

        // These assertions check against a bug where the wrong value from
        // accessories_users was being populated in action_logs.item_id.
        $this->assertActionLogCheckInEntryFor($userA, $accessoryA);
        $this->assertActionLogCheckInEntryFor($userA, $accessoryB);
        $this->assertActionLogCheckInEntryFor($userC, $accessoryA);
    }

    public function test_bulk_user_cleanup_silently_retires_legacy_asset_assignments()
    {
        [$userA, $userB, $userC] = User::factory()->count(3)->create();

        $assetForUserA = $this->assignAssetToUser($userA);
        $lonelyAsset = $this->assignAssetToUser($userB);
        $assetForUserC = $this->assignAssetToUser($userC);
        $acceptanceForUserA = $this->pendingAcceptanceFor($assetForUserA, $userA);
        $lonelyAcceptance = $this->pendingAcceptanceFor($lonelyAsset, $userB);
        $acceptanceForUserC = $this->pendingAcceptanceFor($assetForUserC, $userC);
        $originalStatusIds = [
            $assetForUserA->id => $assetForUserA->status_id,
            $assetForUserC->id => $assetForUserC->status_id,
        ];

        $this->actingAs(User::factory()->deleteUsers()->create())
            ->post(route('users/bulksave'), [
                'ids' => [
                    $userA->id,
                    $userC->id,
                ],
                'delete_user' => '1',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertTrue(User::withTrashed()->findOrFail($userA->id)->assets->isEmpty());
        $this->assertTrue($userB->fresh()->assets->isNotEmpty());
        $this->assertTrue(User::withTrashed()->findOrFail($userC->id)->assets->isEmpty());

        foreach ([$assetForUserA, $assetForUserC] as $asset) {
            $asset->refresh();
            $this->assertNull($asset->assigned_to);
            $this->assertNull($asset->assigned_type);
            $this->assertNull($asset->accepted);
            $this->assertNull($asset->expected_checkin);
            $this->assertSame($originalStatusIds[$asset->id], $asset->status_id);
            $this->assertDatabaseMissing('action_logs', [
                'action_type' => 'checkin from',
                'item_type' => Asset::class,
                'item_id' => $asset->id,
            ]);
        }

        $this->assertSoftDeleted($acceptanceForUserA);
        $this->assertSoftDeleted($acceptanceForUserC);
        $this->assertNotSoftDeleted($lonelyAcceptance);
        $this->assertSame($userB->id, $lonelyAsset->fresh()->assigned_to);
        $this->assertNotNull($lonelyAsset->fresh()->expected_checkin);
    }

    public function test_consumables_can_be_bulk_checked_in()
    {
        [$consumableA, $consumableB] = Consumable::factory()->count(2)->create();
        [$userA, $userB, $userC] = User::factory()->count(3)->create();

        // Add checkouts for multiple consumables to multiple users to get different ids in the mix
        $this->attachConsumableToUsers($consumableA, [$userA, $userB, $userC]);
        $this->attachConsumableToUsers($consumableB, [$userA, $userB]);

        $this->actingAs(User::factory()->deleteUsers()->create())
            ->post(route('users/bulksave'), [
                'ids' => [
                    $userA->id,
                    $userC->id,
                ],
                'delete_user' => '1',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertTrue(User::withTrashed()->findOrFail($userA->id)->consumables->isEmpty());
        $this->assertTrue($userB->fresh()->consumables->isNotEmpty());
        $this->assertTrue(User::withTrashed()->findOrFail($userC->id)->consumables->isEmpty());

        // Consumable checkin should not be logged.
        $this->assertNoActionLogCheckInEntryFor($userA, $consumableA);
        $this->assertNoActionLogCheckInEntryFor($userA, $consumableB);
        $this->assertNoActionLogCheckInEntryFor($userC, $consumableA);
    }

    public function test_license_seats_can_be_bulk_checked_in()
    {
        [$userA, $userB, $userC] = User::factory()->count(3)->create();

        $licenseSeatForUserA = LicenseSeat::factory()->assignedToUser($userA)->create();
        $lonelyLicenseSeat = LicenseSeat::factory()->assignedToUser($userB)->create();
        $licenseSeatForUserC = LicenseSeat::factory()->assignedToUser($userC)->create();

        $this->actingAs(User::factory()->deleteUsers()->create())
            ->post(route('users/bulksave'), [
                'ids' => [
                    $userA->id,
                    $userC->id,
                ],
                'delete_user' => '1',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', trans('general.bulk_checkin_delete_success'));

        $this->assertDatabaseMissing('license_seats', [
            'license_id' => $licenseSeatForUserA->license->id,
            'assigned_to' => $userA->id,
        ]);

        $this->assertDatabaseMissing('license_seats', [
            'license_id' => $licenseSeatForUserC->license->id,
            'assigned_to' => $userC->id,
        ]);

        // Slightly different from the other assertions since we use
        // the license and not the license seat in this case.
        $this->assertDatabaseHas('action_logs', [
            'action_type' => 'checkin from',
            'target_id' => $userA->id,
            'target_type' => User::class,
            'note' => 'Bulk checkin items',
            'item_type' => License::class,
            'item_id' => $licenseSeatForUserA->license->id,
        ]);

        $this->assertDatabaseHas('action_logs', [
            'action_type' => 'checkin from',
            'target_id' => $userC->id,
            'target_type' => User::class,
            'note' => 'Bulk checkin items',
            'item_type' => License::class,
            'item_id' => $licenseSeatForUserC->license->id,
        ]);
    }

    public function test_users_can_be_deleted_in_bulk()
    {
        [$userA, $userB, $userC] = User::factory()->count(3)->create();

        $this->actingAs(User::factory()->deleteUsers()->create())
            ->post(route('users/bulksave'), [
                'ids' => [
                    $userA->id,
                    $userC->id,
                ],
                'delete_user' => '1',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', trans('general.bulk_checkin_delete_success'));

        $this->assertSoftDeleted($userA);
        $this->assertNotSoftDeleted($userB);
        $this->assertSoftDeleted($userC);
    }

    public function test_bulk_delete_is_atomic_when_a_target_is_an_admin(): void
    {
        $actor = User::factory()->deleteUsers()->create();
        $regularUser = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($actor)
            ->post(route('users/bulksave'), [
                'ids' => [$regularUser->id, $admin->id],
                'delete_user' => '1',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('error', trans('general.insufficient_permissions'));

        $this->assertNotSoftDeleted($regularUser);
        $this->assertNotSoftDeleted($admin);
    }

    public function test_admin_cannot_bulk_delete_a_superuser(): void
    {
        $actor = User::factory()->admin()->create();
        $superuser = User::factory()->superuser()->create();

        $this->actingAs($actor)
            ->post(route('users/bulksave'), [
                'ids' => [$superuser->id],
                'delete_user' => '1',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('error', trans('general.insufficient_permissions'));

        $this->assertNotSoftDeleted($superuser);
    }

    public function test_cross_company_ids_cannot_mutate_hidden_user_assignments(): void
    {
        $this->settings->enableMultipleFullCompanySupport();

        [$companyA, $companyB] = Company::factory()->count(2)->create();
        $actor = User::factory()->deleteUsers()->for($companyA)->create();
        $visibleUser = User::factory()->for($companyA)->create();
        $hiddenUser = User::factory()->for($companyB)->create();
        $licenseSeat = LicenseSeat::factory()->assignedToUser($hiddenUser)->create();
        $consumable = Consumable::factory()->create();
        $accessory = Accessory::factory()->create();
        $this->attachConsumableToUsers($consumable, [$hiddenUser]);
        $this->attachAccessoryToUsers($accessory, [$hiddenUser]);

        $this->actingAs($actor)
            ->post(route('users/bulksave'), [
                'ids' => [$visibleUser->id, $hiddenUser->id],
                'delete_user' => '1',
            ])
            ->assertForbidden();

        $this->assertNotSoftDeleted($visibleUser);
        $this->assertNotSoftDeleted($hiddenUser);
        $this->assertDatabaseHas('license_seats', [
            'id' => $licenseSeat->id,
            'assigned_to' => $hiddenUser->id,
        ]);
        $this->assertDatabaseHas('consumables_users', [
            'consumable_id' => $consumable->id,
            'assigned_to' => $hiddenUser->id,
        ]);
        $this->assertDatabaseHas('accessories_checkout', [
            'accessory_id' => $accessory->id,
            'assigned_type' => User::class,
            'assigned_to' => $hiddenUser->id,
        ]);
        $this->assertDatabaseMissing('action_logs', [
            'action_type' => 'checkin from',
            'target_id' => $hiddenUser->id,
        ]);
    }

    private function assignAssetToUser(User $user): Asset
    {
        return Asset::factory()->assignedToUser($user)->create([
            'accepted' => 'pending',
            'expected_checkin' => now()->addDay(),
        ]);
    }

    private function pendingAcceptanceFor(Asset $asset, User $user): CheckoutAcceptance
    {
        return CheckoutAcceptance::factory()->pending()->create([
            'checkoutable_type' => Asset::class,
            'checkoutable_id' => $asset->id,
            'assigned_to_id' => $user->id,
        ]);
    }

    private function attachAccessoryToUsers(Accessory $accessory, array $users): void
    {
        foreach ($users as $user) {
            $accessoryCheckout = $accessory->checkouts()->make();
            $accessoryCheckout->assignedTo()->associate($user);
            $accessoryCheckout->save();
        }
    }

    private function attachConsumableToUsers(Consumable $consumable, array $users): void
    {
        foreach ($users as $user) {
            $consumable->users()->attach($consumable->id, [
                'consumable_id' => $consumable->id,
                'assigned_to' => $user->id,
            ]);
        }
    }

    private function assertActionLogCheckInEntryFor(User $user, Model $model): void
    {
        $this->assertDatabaseHas('action_logs', [
            'action_type' => 'checkin from',
            'target_id' => $user->id,
            'target_type' => User::class,
            'note' => 'Bulk checkin items',
            'item_type' => get_class($model),
            'item_id' => $model->id,
        ]);
    }

    private function assertNoActionLogCheckInEntryFor(User $user, Model $model): void
    {
        $this->assertDatabaseMissing('action_logs', [
            'action_type' => 'checkin from',
            'target_id' => $user->id,
            'target_type' => User::class,
            'note' => 'Bulk checkin items',
            'item_type' => get_class($model),
            'item_id' => $model->id,
        ]);
    }
}
