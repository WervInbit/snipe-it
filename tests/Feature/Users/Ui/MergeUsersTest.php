<?php

namespace Tests\Feature\Users\Ui;

use App\Events\UserMerged;
use App\Models\Accessory;
use App\Models\Asset;
use App\Models\Consumable;
use App\Models\LicenseSeat;
use App\Models\User;
use App\Models\Actionlog;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;


class MergeUsersTest extends TestCase
{
    public function testUserEditPermissionCannotBypassDeletePermissionDuringMerge(): void
    {
        $source = User::factory()->create();
        $destination = User::factory()->create();

        $this->actingAs(User::factory()->editUsers()->viewUsers()->create())
            ->post(route('users.merge.save'), [
                'ids_to_merge' => [$source->id],
                'merge_into_id' => $destination->id,
            ])
            ->assertForbidden();

        $this->assertNotSoftDeleted($source);
    }

    public function testGranularUserManagerCannotMergeAnAdminSource(): void
    {
        $source = User::factory()->admin()->create();
        $destination = User::factory()->create();

        $this->assertMergeDenied(
            User::factory()->editUsers()->deleteUsers()->viewUsers()->create(),
            $source,
            $destination
        );
    }

    public function testGranularUserManagerCannotMergeIntoAnAdminDestination(): void
    {
        $source = User::factory()->create();
        $destination = User::factory()->admin()->create();

        $this->assertMergeDenied(
            User::factory()->editUsers()->deleteUsers()->viewUsers()->create(),
            $source,
            $destination
        );
    }

    public function testAdminCannotMergeASuperuserSource(): void
    {
        $source = User::factory()->superuser()->create();
        $destination = User::factory()->create();

        $this->assertMergeDenied(
            User::factory()->admin()->create(),
            $source,
            $destination
        );
    }

    public function testAdminCannotMergeIntoASuperuserDestination(): void
    {
        $source = User::factory()->create();
        $destination = User::factory()->superuser()->create();

        $this->assertMergeDenied(
            User::factory()->admin()->create(),
            $source,
            $destination
        );
    }

    public function testMergeRollsBackEveryChangeWhenAListenerFails(): void
    {
        $source = User::factory()->create();
        $destination = User::factory()->create();
        $asset = Asset::factory()->assignedToUser($source)->create();

        Event::listen(UserMerged::class, function (): void {
            throw new RuntimeException('forced merge failure');
        });

        $this->withoutExceptionHandling();

        try {
            $this->actingAs(User::factory()->editUsers()->deleteUsers()->viewUsers()->create())
                ->post(route('users.merge.save'), [
                    'ids_to_merge' => [$source->id],
                    'merge_into_id' => $destination->id,
                ]);

            $this->fail('The forced merge failure was not raised.');
        } catch (RuntimeException $exception) {
            $this->assertSame('forced merge failure', $exception->getMessage());
        }

        $this->assertNotSoftDeleted($source);
        $this->assertSame($source->id, $asset->fresh()->assigned_to);
        $this->assertSame(User::class, $asset->fresh()->assigned_type);
    }

    public function testLegacyAssetAssignmentsAreClearedInsteadOfTransferredOnUserMerge()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user_to_merge_into = User::factory()->create();

        $sourceAssets = Asset::factory()->count(3)->assignedToUser($user1)->create()
            ->merge(Asset::factory()->count(3)->assignedToUser($user2)->create());
        $destinationAssets = Asset::factory()->count(3)->assignedToUser($user_to_merge_into)->create();

        $response = $this->actingAs(User::factory()->editUsers()->deleteUsers()->viewUsers()->create())
            ->post(route('users.merge.save', $user1->id),
                [
                    'ids_to_merge' => [$user1->id, $user2->id],
                    'merge_into_id' => $user_to_merge_into->id
                ])
            ->assertStatus(302)
            ->assertRedirect(route('users.index'));

        $this->followRedirects($response)->assertSee('Success');
        $this->assertEquals(3, $user_to_merge_into->refresh()->assets->count());
        $this->assertEquals(0, $user1->refresh()->assets->count());
        $this->assertEquals(0, $user2->refresh()->assets->count());

        foreach ($sourceAssets as $asset) {
            $this->assertDatabaseHas('assets', [
                'id' => $asset->id,
                'assigned_to' => null,
                'assigned_type' => null,
            ]);
        }

        foreach ($destinationAssets as $asset) {
            $this->assertDatabaseHas('assets', [
                'id' => $asset->id,
                'assigned_to' => $user_to_merge_into->id,
                'assigned_type' => User::class,
            ]);
        }
    }

    public function testLicensesAreTransferredOnUserMerge()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user_to_merge_into = User::factory()->create();

        LicenseSeat::factory()->count(3)->create(['assigned_to' => $user1->id]);
        LicenseSeat::factory()->count(3)->create(['assigned_to' => $user2->id]);
        LicenseSeat::factory()->count(3)->create(['assigned_to' => $user_to_merge_into->id]);

        $this->assertEquals(3, $user_to_merge_into->refresh()->licenses->count());

        $response = $this->actingAs(User::factory()->editUsers()->deleteUsers()->viewUsers()->create())
            ->post(route('users.merge.save', $user1->id),
                [
                    'ids_to_merge' => [$user1->id, $user2->id],
                    'merge_into_id' => $user_to_merge_into->id
                ])
            ->assertStatus(302)
            ->assertRedirect(route('users.index'));

        $this->followRedirects($response)->assertSee('Success');
        $this->assertEquals(9, $user_to_merge_into->refresh()->licenses->count());
        $this->assertEquals(0, $user1->refresh()->licenses->count());
        $this->assertEquals(0, $user2->refresh()->licenses->count());

    }

    public function testAccessoriesTransferredOnUserMerge()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user_to_merge_into = User::factory()->create();

        Accessory::factory()->count(3)->checkedOutToUser($user1)->create();
        Accessory::factory()->count(3)->checkedOutToUser($user2)->create();
        Accessory::factory()->count(3)->checkedOutToUser($user_to_merge_into)->create();

        $this->assertEquals(3, $user_to_merge_into->refresh()->accessories->count());

        $response = $this->actingAs(User::factory()->editUsers()->deleteUsers()->viewUsers()->create())
            ->post(route('users.merge.save', $user1->id),
                [
                    'ids_to_merge' => [$user1->id, $user2->id],
                    'merge_into_id' => $user_to_merge_into->id
                ])
            ->assertStatus(302)
            ->assertRedirect(route('users.index'));

        $this->followRedirects($response)->assertSee('Success');
        $this->assertEquals(9, $user_to_merge_into->refresh()->accessories->count());
        $this->assertEquals(0, $user1->refresh()->accessories->count());
        $this->assertEquals(0, $user2->refresh()->accessories->count());

    }

    public function testConsumablesTransferredOnUserMerge()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user_to_merge_into = User::factory()->create();

        Consumable::factory()->count(3)->checkedOutToUser($user1)->create();
        Consumable::factory()->count(3)->checkedOutToUser($user2)->create();
        Consumable::factory()->count(3)->checkedOutToUser($user_to_merge_into)->create();

        $this->assertEquals(3, $user_to_merge_into->refresh()->consumables->count());

        $response = $this->actingAs(User::factory()->editUsers()->deleteUsers()->viewUsers()->create())
            ->post(route('users.merge.save', $user1->id),
                [
                    'ids_to_merge' => [$user1->id, $user2->id],
                    'merge_into_id' => $user_to_merge_into->id
                ])
            ->assertStatus(302)
            ->assertRedirect(route('users.index'));

        $this->followRedirects($response)->assertSee('Success');
        $this->assertEquals(9, $user_to_merge_into->refresh()->consumables->count());
        $this->assertEquals(0, $user1->refresh()->consumables->count());
        $this->assertEquals(0, $user2->refresh()->consumables->count());

    }

    public function testFilesAreTransferredOnUserMerge()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user_to_merge_into = User::factory()->create();

        Actionlog::factory()->count(3)->filesUploaded()->create(['item_id' => $user1->id]);
        Actionlog::factory()->count(3)->filesUploaded()->create(['item_id' => $user2->id]);
        Actionlog::factory()->count(3)->filesUploaded()->create(['item_id' => $user_to_merge_into->id]);

        $this->assertEquals(3, $user_to_merge_into->refresh()->uploads->count());

        $response = $this->actingAs(User::factory()->editUsers()->deleteUsers()->viewUsers()->create())
            ->post(route('users.merge.save', $user1->id),
                [
                    'ids_to_merge' => [$user1->id, $user2->id],
                    'merge_into_id' => $user_to_merge_into->id
                ])
            ->assertStatus(302)
            ->assertRedirect(route('users.index'));

        $this->followRedirects($response)->assertSee('Success');
        $this->assertEquals(9, $user_to_merge_into->refresh()->uploads->count());
        $this->assertEquals(0, $user1->refresh()->uploads->count());
        $this->assertEquals(0, $user2->refresh()->uploads->count());

    }

    public function testAcceptancesAreTransferredOnUserMerge()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user_to_merge_into = User::factory()->create();

        $sourceAcceptances = Actionlog::factory()->count(3)->acceptedSignature()->create([
            'target_id' => $user1->id,
        ]);
        Actionlog::factory()->count(3)->acceptedSignature()->create(['target_id' => $user2->id]);
        Actionlog::factory()->count(3)->acceptedSignature()->create(['target_id' => $user_to_merge_into->id]);
        $preservedAcceptance = $sourceAcceptances->first();
        $originalItemId = $preservedAcceptance->item_id;
        $originalItemType = $preservedAcceptance->item_type;

        $this->assertEquals(3, $user_to_merge_into->refresh()->acceptances->count());

        $response = $this->actingAs(User::factory()->editUsers()->deleteUsers()->viewUsers()->create())
            ->post(route('users.merge.save', $user1->id),
                [
                    'ids_to_merge' => [$user1->id, $user2->id],
                    'merge_into_id' => $user_to_merge_into->id
                ])
            ->assertStatus(302)
            ->assertRedirect(route('users.index'));

        $this->followRedirects($response)->assertSee('Success');
        $this->assertEquals(9, $user_to_merge_into->refresh()->acceptances->count());
        $this->assertEquals(0, $user1->refresh()->acceptances->count());
        $this->assertEquals(0, $user2->refresh()->acceptances->count());
        $preservedAcceptance->refresh();
        $this->assertSame($user_to_merge_into->id, $preservedAcceptance->target_id);
        $this->assertSame($originalItemId, $preservedAcceptance->item_id);
        $this->assertSame($originalItemType, $preservedAcceptance->item_type);

    }

    public function testUserUpdateHistoryIsTransferredOnUserMerge()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user_to_merge_into = User::factory()->create();

        Actionlog::factory()->count(3)->userUpdated()->create(['target_id' => $user1->id, 'item_id' => $user1->id]);
        Actionlog::factory()->count(3)->userUpdated()->create(['target_id' => $user2->id, 'item_id' => $user2->id]);
        Actionlog::factory()->count(3)->userUpdated()->create(['target_id' => $user_to_merge_into->id, 'item_id' => $user_to_merge_into->id]);

        $this->assertEquals(3, $user_to_merge_into->refresh()->userlog->count());

        $response = $this->actingAs(User::factory()->editUsers()->deleteUsers()->viewUsers()->create())
            ->post(route('users.merge.save', $user1->id),
                [
                    'ids_to_merge' => [$user1->id, $user2->id],
                    'merge_into_id' => $user_to_merge_into->id
                ])
            ->assertStatus(302)
            ->assertRedirect(route('users.index'));

        $this->followRedirects($response)->assertSee('Success');

        // This needs to be 2 more than the otherwise expected because the merge action itself is logged for the two merging users
        $this->assertEquals(11, $user_to_merge_into->refresh()->userlog->count());
        $this->assertEquals(2, $user1->refresh()->userlog->count());
        $this->assertEquals(2, $user2->refresh()->userlog->count());

    }

    private function assertMergeDenied(User $actor, User $source, User $destination): void
    {
        $this->actingAs($actor)
            ->post(route('users.merge.save'), [
                'ids_to_merge' => [$source->id],
                'merge_into_id' => $destination->id,
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('error', trans('general.insufficient_permissions'));

        $this->assertNotSoftDeleted($source);
        $this->assertNotSoftDeleted($destination);
    }

}
