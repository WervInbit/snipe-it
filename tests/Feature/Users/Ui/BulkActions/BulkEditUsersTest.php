<?php

namespace Tests\Feature\Users\Ui\BulkActions;

use App\Models\Asset;
use App\Models\Company;
use App\Models\Group;
use App\Models\Location;
use App\Models\User;
use Tests\TestCase;

class BulkEditUsersTest extends TestCase
{
    public function testChangingUserLocationsDoesNotMoveAssetsWithLegacyAssignmentState(): void
    {
        [$originalLocation, $newLocation] = Location::factory()->count(2)->create();
        $user = User::factory()->create(['location_id' => $originalLocation->id]);
        $asset = Asset::factory()->create(['location_id' => $originalLocation->id]);
        $asset->forceFill([
            'assigned_to' => $user->id,
            'assigned_type' => User::class,
        ])->saveQuietly();

        $this->actingAs(User::factory()->editUsers()->create())
            ->post(route('users/bulkeditsave'), [
                'ids' => [$user->id],
                'location_id' => $newLocation->id,
            ])
            ->assertRedirect(route('users.index'));

        $this->assertSame($newLocation->id, $user->fresh()->location_id);
        $this->assertSame($originalLocation->id, $asset->fresh()->location_id);
        $this->assertSame($user->id, $asset->fresh()->assigned_to);
    }

    public function testNonSuperuserCannotChangeGroupsThroughBulkEndpoint(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();

        $this->actingAs(User::factory()->editUsers()->create())
            ->post(route('users/bulkeditsave'), [
                'ids' => [$user->id],
                'groups' => [$group->id],
            ])
            ->assertForbidden();

        $this->assertFalse($user->fresh()->groups->contains($group));
    }

    public function testCompanyScopedEditorCannotMoveUsersToAnotherCompanyThroughBulkEndpoint(): void
    {
        $this->settings->enableMultipleFullCompanySupport();
        [$companyA, $companyB] = Company::factory()->count(2)->create();
        $actor = User::factory()->editUsers()->for($companyA)->create();
        $user = User::factory()->for($companyA)->create();

        $this->actingAs($actor)
            ->post(route('users/bulkeditsave'), [
                'ids' => [$user->id],
                'company_id' => $companyB->id,
            ])
            ->assertForbidden();

        $this->assertSame($companyA->id, $user->fresh()->company_id);
    }

    public function testInvalidBulkRelationIdsAreRejectedBeforeAnyUserChanges(): void
    {
        [$userA, $userB] = User::factory()->count(2)->create(['city' => 'Original']);

        $this->actingAs(User::factory()->editUsers()->create())
            ->from(route('users.index'))
            ->post(route('users/bulkeditsave'), [
                'ids' => [$userA->id, $userB->id],
                'city' => 'Changed',
                'manager_id' => 999999,
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHasErrors('manager_id');

        $this->assertSame('Original', $userA->fresh()->city);
        $this->assertSame('Original', $userB->fresh()->city);
    }

    public function testAuthFieldsAreFilteredPerTargetWithoutDiscardingProfileUpdates(): void
    {
        $actor = User::factory()->editUsers()->create();
        $regularUser = User::factory()->create([
            'activated' => 1,
            'ldap_import' => 0,
            'city' => 'Original',
        ]);
        $admin = User::factory()->admin()->create([
            'activated' => 1,
            'ldap_import' => 0,
            'city' => 'Original',
        ]);

        $this->actingAs($actor)
            ->post(route('users/bulkeditsave'), [
                'ids' => [$regularUser->id, $admin->id],
                'activated' => '0',
                'ldap_import' => '1',
                'city' => 'Updated',
            ])
            ->assertRedirect(route('users.index'));

        $regularUser->refresh();
        $this->assertEquals(0, $regularUser->activated);
        $this->assertEquals(1, $regularUser->ldap_import);
        $this->assertSame('Updated', $regularUser->city);

        $admin->refresh();
        $this->assertEquals(1, $admin->activated);
        $this->assertEquals(0, $admin->ldap_import);
        $this->assertSame('Updated', $admin->city);
    }

    public function testAdminCannotBulkChangeSuperuserAuthFields(): void
    {
        $superuser = User::factory()->superuser()->create([
            'activated' => 1,
            'ldap_import' => 0,
            'city' => 'Original',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('users/bulkeditsave'), [
                'ids' => [$superuser->id],
                'activated' => '0',
                'ldap_import' => '1',
                'city' => 'Updated',
            ])
            ->assertRedirect(route('users.index'));

        $superuser->refresh();
        $this->assertEquals(1, $superuser->activated);
        $this->assertEquals(0, $superuser->ldap_import);
        $this->assertSame('Updated', $superuser->city);
    }
}
