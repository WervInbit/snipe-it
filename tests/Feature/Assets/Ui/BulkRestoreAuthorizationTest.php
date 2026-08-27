<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\User;
use Tests\TestCase;

class BulkRestoreAuthorizationTest extends TestCase
{
    public function testEditPermissionDoesNotAuthorizeBulkRestore(): void
    {
        $asset = Asset::factory()->deleted()->create();
        $user = User::factory()->viewAssets()->editAssets()->create();

        $this->actingAs($user)
            ->post(route('hardware/bulkrestore'), ['ids' => [$asset->id]])
            ->assertForbidden();

        $this->assertNotNull(Asset::withTrashed()->findOrFail($asset->id)->deleted_at);
    }

    public function testDeletePermissionAuthorizesBulkRestore(): void
    {
        $asset = Asset::factory()->deleted()->create();
        $user = User::factory()->viewAssets()->deleteAssets()->create();

        $this->actingAs($user)
            ->post(route('hardware/bulkrestore'), ['ids' => [$asset->id]])
            ->assertRedirect(route('hardware.index'))
            ->assertSessionHas('success');

        $this->assertNull(Asset::withTrashed()->findOrFail($asset->id)->deleted_at);
    }

    public function testBulkRestoreSkipsInvalidIdsAndRestoresValidDeletedAssets(): void
    {
        $asset = Asset::factory()->deleted()->create();
        $user = User::factory()->viewAssets()->deleteAssets()->create();

        $this->actingAs($user)
            ->post(route('hardware/bulkrestore'), [
                'ids' => [$asset->id, 99999999, ['nested' => 'invalid']],
            ])
            ->assertRedirect(route('hardware.index'))
            ->assertSessionHas('success');

        $this->assertNull(Asset::withTrashed()->findOrFail($asset->id)->deleted_at);
    }

    public function testBulkRestoreNeverMutatesAnActiveAsset(): void
    {
        $assignedUser = User::factory()->create();
        $activeAsset = Asset::factory()->assignedToUser($assignedUser)->create();
        $user = User::factory()->viewAssets()->deleteAssets()->create();

        $this->actingAs($user)
            ->post(route('hardware/bulkrestore'), ['ids' => [$activeAsset->id]])
            ->assertRedirect(route('hardware.index'))
            ->assertSessionHas('error');

        $activeAsset->refresh();
        $this->assertSame($assignedUser->id, $activeAsset->assigned_to);
        $this->assertSame(User::class, $activeAsset->assigned_type);
    }
}
