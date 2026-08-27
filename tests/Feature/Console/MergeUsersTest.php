<?php

namespace Tests\Feature\Console;

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
    public function testAmbiguousDestinationUsernamesAreSkipped(): void
    {
        $source = User::factory()->create(['username' => 'user1']);
        User::factory()->create(['username' => 'user1@example.com']);
        User::factory()->create(['username' => 'user1@other.example']);

        $this->artisan('snipeit:merge-users')
            ->expectsOutput('Skipping user1 because multiple destination usernames match this account.')
            ->assertExitCode(0);

        $this->assertNotSoftDeleted($source);
    }

    public function testMergeRollsBackWhenAListenerFails(): void
    {
        $source = User::factory()->create(['username' => 'user1']);
        User::factory()->create(['username' => 'user1@example.com']);
        $asset = Asset::factory()->assignedToUser($source)->create();

        Event::listen(UserMerged::class, function (): void {
            throw new RuntimeException('forced CLI merge failure');
        });

        try {
            $this->artisan('snipeit:merge-users');
            $this->fail('The forced merge failure was not raised.');
        } catch (RuntimeException $exception) {
            $this->assertSame('forced CLI merge failure', $exception->getMessage());
        }

        $this->assertNotSoftDeleted($source);
        $this->assertSame($source->id, $asset->refresh()->assigned_to);
        $this->assertSame(User::class, $asset->assigned_type);
    }

    public function testLegacyAssetAssignmentsAreClearedInsteadOfTransferredOnUserMerge()
    {
        $user1 = User::factory()->create(['username' => 'user1']);
        $user_to_merge_into = User::factory()->create(['username' => 'user1@example.com']);

        $sourceAssets = Asset::factory()->count(3)->assignedToUser($user1)->create();
        $destinationAssets = Asset::factory()->count(3)->assignedToUser($user_to_merge_into)->create();

        $this->artisan('snipeit:merge-users')->assertExitCode(0);

        $this->assertEquals(3, $user_to_merge_into->refresh()->assets->count());
        $this->assertEquals(0, $user1->refresh()->assets->count());

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

    public function testLicensesAreTransferredOnUserMerge(): void
    {
        $user1 = User::factory()->create(['username' => 'user1']);
        $user_to_merge_into = User::factory()->create(['username' => 'user1@example.com']);

        LicenseSeat::factory()->count(3)->create(['assigned_to' => $user1->id]);
        LicenseSeat::factory()->count(3)->create(['assigned_to' => $user_to_merge_into->id]);

        $this->assertEquals(3, $user_to_merge_into->refresh()->licenses->count());

        $this->artisan('snipeit:merge-users')->assertExitCode(0);

        $this->assertEquals(6, $user_to_merge_into->refresh()->licenses->count());
        $this->assertEquals(0, $user1->refresh()->licenses->count());

    }

    public function testAccessoriesTransferredOnUserMerge(): void
    {
        $user1 = User::factory()->create(['username' => 'user1']);
        $user_to_merge_into = User::factory()->create(['username' => 'user1@example.com']);

        Accessory::factory()->count(3)->checkedOutToUser($user1)->create();
        Accessory::factory()->count(3)->checkedOutToUser($user_to_merge_into)->create();

        $this->assertEquals(3, $user_to_merge_into->refresh()->accessories->count());

        $this->artisan('snipeit:merge-users')->assertExitCode(0);

        $this->assertEquals(6, $user_to_merge_into->refresh()->accessories->count());
        $this->assertEquals(0, $user1->refresh()->accessories->count());

    }

    public function testConsumablesTransferredOnUserMerge(): void
    {
        $user1 = User::factory()->create(['username' => 'user1']);
        $user_to_merge_into = User::factory()->create(['username' => 'user1@example.com']);

        Consumable::factory()->count(3)->checkedOutToUser($user1)->create();
        Consumable::factory()->count(3)->checkedOutToUser($user_to_merge_into)->create();

        $this->assertEquals(3, $user_to_merge_into->refresh()->consumables->count());

        $this->artisan('snipeit:merge-users')->assertExitCode(0);

        $this->assertEquals(6, $user_to_merge_into->refresh()->consumables->count());
        $this->assertEquals(0, $user1->refresh()->consumables->count());

    }

    public function testFilesAreTransferredOnUserMerge(): void
    {
        $user1 = User::factory()->create(['username' => 'user1']);
        $user_to_merge_into = User::factory()->create(['username' => 'user1@example.com']);

        Actionlog::factory()->count(3)->filesUploaded()->create(['item_id' => $user1->id]);
        Actionlog::factory()->count(3)->filesUploaded()->create(['item_id' => $user_to_merge_into->id]);

        $this->assertEquals(3, $user_to_merge_into->refresh()->uploads->count());

        $this->artisan('snipeit:merge-users')->assertExitCode(0);

        $this->assertEquals(6, $user_to_merge_into->refresh()->uploads->count());
        $this->assertEquals(0, $user1->refresh()->uploads->count());

    }

    public function testAcceptancesAreTransferredOnUserMerge(): void
    {
        $user1 = User::factory()->create(['username' => 'user1']);
        $user_to_merge_into = User::factory()->create(['username' => 'user1@example.com']);

        $sourceAcceptances = Actionlog::factory()->count(3)->acceptedSignature()->create([
            'target_id' => $user1->id,
        ]);
        Actionlog::factory()->count(3)->acceptedSignature()->create(['target_id' => $user_to_merge_into->id]);
        $preservedAcceptance = $sourceAcceptances->first();
        $originalItemId = $preservedAcceptance->item_id;
        $originalItemType = $preservedAcceptance->item_type;

        $this->assertEquals(3, $user_to_merge_into->refresh()->acceptances->count());

        $this->artisan('snipeit:merge-users')->assertExitCode(0);

        $this->assertEquals(6, $user_to_merge_into->refresh()->acceptances->count());
        $this->assertEquals(0, $user1->refresh()->acceptances->count());
        $preservedAcceptance->refresh();
        $this->assertSame($user_to_merge_into->id, $preservedAcceptance->target_id);
        $this->assertSame($originalItemId, $preservedAcceptance->item_id);
        $this->assertSame($originalItemType, $preservedAcceptance->item_type);

    }

    public function testUserUpdateHistoryIsTransferredOnUserMerge(): void
    {
        $user1 = User::factory()->create(['username' => 'user1']);
        $user_to_merge_into = User::factory()->create(['username' => 'user1@example.com']);

        Actionlog::factory()->count(3)->userUpdated()->create(['target_id' => $user1->id, 'item_id' => $user1->id]);
        Actionlog::factory()->count(3)->userUpdated()->create(['target_id' => $user_to_merge_into->id, 'item_id' => $user_to_merge_into->id]);

        $this->assertEquals(3, $user_to_merge_into->refresh()->userlog->count());

        $this->artisan('snipeit:merge-users')->assertExitCode(0);

        // This needs to be more than the otherwise expected because the merge action itself is logged for the two merging users
        $this->assertEquals(7, $user_to_merge_into->refresh()->userlog->count());
        $this->assertEquals(2, $user1->refresh()->userlog->count());

    }


}
