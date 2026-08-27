<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\AssetTest;
use App\Models\User;
use Tests\TestCase;

class AssetTestNestedBindingTest extends TestCase
{
    public function test_web_member_routes_bind_and_mutate_the_requested_asset_test(): void
    {
        $asset = Asset::factory()->create();
        $user = User::factory()->superuser()->create();
        $assetTest = $this->createAssetTest($asset, $user, ['status' => 'pending']);

        $this->actingAs($user)
            ->get(route('asset-tests.edit', [$asset, $assetTest]))
            ->assertOk();

        $this->actingAs($user)
            ->put(route('asset-tests.update', [$asset, $assetTest]), [
                'performed_at' => now()->toDateTimeString(),
                'status' => 'passed',
                'needs_cleaning' => false,
                'notes' => 'Updated through the nested web route.',
            ])
            ->assertRedirect(route('asset-tests.index', $asset));

        $this->assertDatabaseHas('asset_tests', [
            'id' => $assetTest->id,
            'asset_id' => $asset->id,
            'status' => 'passed',
        ]);

        $this->actingAs($user)
            ->post(route('asset-tests.repeat', [$asset, $assetTest]))
            ->assertRedirect(route('asset-tests.index', $asset));

        $this->assertSame(2, AssetTest::query()->where('asset_id', $asset->id)->count());

        $this->actingAs($user)
            ->delete(route('asset-tests.destroy', [$asset, $assetTest]))
            ->assertRedirect(route('asset-tests.index', $asset));

        $this->assertSoftDeleted('asset_tests', ['id' => $assetTest->id]);
    }

    public function test_api_member_routes_bind_and_mutate_the_requested_asset_test(): void
    {
        $asset = Asset::factory()->create();
        $user = User::factory()->superuser()->create();
        $assetTest = $this->createAssetTest($asset, $user, ['status' => 'pending']);

        $this->actingAsForApi($user)
            ->putJson(route('api.asset-tests.update', [$asset, $assetTest]), [
                'performed_at' => now()->toDateTimeString(),
                'status' => 'passed',
                'needs_cleaning' => true,
                'notes' => 'Updated through the nested API route.',
            ])
            ->assertOk()
            ->assertJsonPath('id', $assetTest->id)
            ->assertJsonPath('status', 'passed');

        $this->assertDatabaseHas('action_logs', [
            'item_type' => AssetTest::class,
            'item_id' => $assetTest->id,
            'action_type' => 'update',
            'created_by' => $user->id,
        ]);

        $repeatResponse = $this->actingAsForApi($user)
            ->postJson(route('api.asset-tests.repeat', [$asset, $assetTest]))
            ->assertCreated()
            ->assertJsonPath('asset_id', $asset->id);
        $repeatedId = (int) $repeatResponse->json('id');

        $this->assertDatabaseHas('action_logs', [
            'item_type' => AssetTest::class,
            'item_id' => $repeatedId,
            'action_type' => 'create',
            'created_by' => $user->id,
        ]);

        $this->actingAsForApi($user)
            ->deleteJson(route('api.asset-tests.destroy', [$asset, $assetTest]))
            ->assertNoContent();

        $this->assertSoftDeleted('asset_tests', ['id' => $assetTest->id]);
        $this->assertDatabaseHas('action_logs', [
            'item_type' => AssetTest::class,
            'item_id' => $assetTest->id,
            'action_type' => 'delete',
            'created_by' => $user->id,
        ]);
    }

    public function test_web_member_routes_reject_an_asset_test_from_another_asset(): void
    {
        $asset = Asset::factory()->create();
        $otherAsset = Asset::factory()->create();
        $user = User::factory()->superuser()->create();
        $assetTest = $this->createAssetTest($otherAsset, $user);

        $this->actingAs($user)
            ->get(route('asset-tests.edit', [$asset, $assetTest]))
            ->assertNotFound();

        $this->actingAs($user)
            ->put(route('asset-tests.update', [$asset, $assetTest]), $this->validUpdate())
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('asset-tests.repeat.form', [$asset, $assetTest]))
            ->assertNotFound();

        $this->actingAs($user)
            ->post(route('asset-tests.repeat', [$asset, $assetTest]))
            ->assertNotFound();

        $this->actingAs($user)
            ->delete(route('asset-tests.destroy', [$asset, $assetTest]))
            ->assertNotFound();

        $this->assertDatabaseHas('asset_tests', [
            'id' => $assetTest->id,
            'asset_id' => $otherAsset->id,
            'deleted_at' => null,
        ]);
    }

    public function test_api_member_routes_reject_an_asset_test_from_another_asset(): void
    {
        $asset = Asset::factory()->create();
        $otherAsset = Asset::factory()->create();
        $user = User::factory()->superuser()->create();
        $assetTest = $this->createAssetTest($otherAsset, $user);

        $this->actingAsForApi($user)
            ->putJson(route('api.asset-tests.update', [$asset, $assetTest]), $this->validUpdate())
            ->assertNotFound();

        $this->actingAsForApi($user)
            ->postJson(route('api.asset-tests.repeat', [$asset, $assetTest]))
            ->assertNotFound();

        $this->actingAsForApi($user)
            ->deleteJson(route('api.asset-tests.destroy', [$asset, $assetTest]))
            ->assertNotFound();

        $this->assertDatabaseHas('asset_tests', [
            'id' => $assetTest->id,
            'asset_id' => $otherAsset->id,
            'deleted_at' => null,
        ]);
    }

    private function createAssetTest(Asset $asset, User $user, array $overrides = []): AssetTest
    {
        return AssetTest::query()->create(array_merge([
            'asset_id' => $asset->id,
            'performed_at' => now()->subMinute(),
            'status' => 'pending',
            'needs_cleaning' => false,
            'notes' => null,
            'created_by' => $user->id,
        ], $overrides));
    }

    private function validUpdate(): array
    {
        return [
            'performed_at' => now()->toDateTimeString(),
            'status' => 'passed',
            'needs_cleaning' => false,
            'notes' => 'Must not cross the nested asset boundary.',
        ];
    }
}
