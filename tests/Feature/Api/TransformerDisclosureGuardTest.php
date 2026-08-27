<?php

namespace Tests\Feature\Api;

use App\Http\Transformers\AssetsTransformer;
use App\Http\Transformers\LicenseSeatsTransformer;
use App\Http\Transformers\UsersTransformer;
use App\Models\Accessory;
use App\Models\Asset;
use App\Models\Component;
use App\Models\LicenseSeat;
use App\Models\User;
use Tests\TestCase;

class TransformerDisclosureGuardTest extends TestCase
{
    public function test_full_user_transformer_strips_private_fields_without_user_view_permission(): void
    {
        $target = User::factory()->create([
            'first_name' => 'Hidden',
            'last_name' => 'Person',
            'username' => 'hidden-person',
            'email' => 'hidden@example.test',
        ]);

        $this->actingAs(User::factory()->create());

        $payload = (new UsersTransformer)->transformUser($target);

        $this->assertSame([
            'id' => $target->id,
            'type' => 'user',
            'name' => e($target->getFullNameAttribute()),
        ], $payload);
    }

    public function test_full_user_transformer_preserves_the_authenticated_users_own_profile(): void
    {
        $actor = User::factory()->create([
            'username' => 'self-profile',
            'email' => 'self@example.test',
        ]);

        $this->actingAs($actor);

        $payload = (new UsersTransformer)->transformUser($actor);

        $this->assertSame('self-profile', $payload['username']);
        $this->assertSame('self@example.test', $payload['email']);
        $this->assertArrayHasKey('permissions', $payload);
    }

    public function test_accessory_checkout_uses_compact_user_fallback_without_user_view_permission(): void
    {
        $assignee = User::factory()->create([
            'first_name' => 'Hidden',
            'last_name' => 'Assignee',
            'username' => 'hidden-assignee',
        ]);
        $accessory = Accessory::factory()->checkedOutToUsers([$assignee])->create();

        $response = $this->actingAsForApi(User::factory()->viewAccessories()->create())
            ->getJson(route('api.accessories.checkedout', $accessory))
            ->assertOk();

        $this->assertSame([
            'id' => $assignee->id,
            'type' => 'user',
            'name' => e($assignee->getFullNameAttribute()),
        ], $response->json('rows.0.assigned_to'));
    }

    public function test_accessory_checkout_keeps_full_compact_user_data_with_user_view_permission(): void
    {
        $assignee = User::factory()->create([
            'first_name' => 'Visible',
            'last_name' => 'Assignee',
            'username' => 'visible-assignee',
        ]);
        $accessory = Accessory::factory()->checkedOutToUsers([$assignee])->create();
        $actor = User::factory()->viewAccessories()->viewUsers()->create();

        $this->actingAsForApi($actor)
            ->getJson(route('api.accessories.checkedout', $accessory))
            ->assertOk()
            ->assertJsonPath('rows.0.assigned_to.username', 'visible-assignee')
            ->assertJsonPath('rows.0.assigned_to.first_name', 'Visible');
    }

    public function test_asset_assignee_strips_private_user_fields_without_user_view_permission(): void
    {
        $assignee = User::factory()->create([
            'username' => 'asset-assignee',
            'email' => 'asset-assignee@example.test',
        ]);
        $asset = Asset::with('assignedTo')->findOrFail(
            Asset::factory()->assignedToUser($assignee)->create()->id
        );

        $this->actingAs(User::factory()->viewAssets()->create());

        $payload = (new AssetsTransformer)->transformAssignedTo($asset);

        $this->assertSame([
            'id' => $assignee->id,
            'type' => 'user',
            'name' => e($assignee->getFullNameAttribute()),
        ], $payload);
    }

    public function test_asset_assignee_keeps_private_user_fields_with_user_view_permission(): void
    {
        $assignee = User::factory()->create([
            'username' => 'visible-asset-assignee',
            'email' => 'visible-asset-assignee@example.test',
        ]);
        $asset = Asset::with('assignedTo')->findOrFail(
            Asset::factory()->assignedToUser($assignee)->create()->id
        );
        $actor = User::factory()->viewAssets()->viewUsers()->create();

        $this->actingAs($actor);

        $payload = (new AssetsTransformer)->transformAssignedTo($asset);

        $this->assertSame('visible-asset-assignee', $payload['username']);
        $this->assertSame('visible-asset-assignee@example.test', $payload['email']);
    }

    public function test_license_seat_assignee_strips_private_user_fields_without_user_view_permission(): void
    {
        $assignee = User::factory()->create([
            'username' => 'license-assignee',
            'email' => 'license-assignee@example.test',
        ]);
        $seat = LicenseSeat::factory()->assignedToUser($assignee)->create();

        $this->actingAs(User::factory()->viewLicenses()->create());

        $payload = (new LicenseSeatsTransformer)->transformLicenseSeat($seat);

        $this->assertSame([
            'id' => $assignee->id,
            'type' => 'user',
            'name' => e($assignee->getFullNameAttribute()),
        ], $payload['assigned_user']);
    }

    public function test_license_seat_assignee_keeps_private_user_fields_with_user_view_permission(): void
    {
        $assignee = User::factory()->create([
            'username' => 'visible-license-assignee',
            'email' => 'visible-license-assignee@example.test',
        ]);
        $seat = LicenseSeat::factory()->assignedToUser($assignee)->create();
        $actor = User::factory()->viewLicenses()->viewUsers()->create();

        $this->actingAs($actor);

        $payload = (new LicenseSeatsTransformer)->transformLicenseSeat($seat);

        $this->assertSame('visible-license-assignee@example.test', $payload['assigned_user']['email']);
        $this->assertArrayHasKey('department', $payload['assigned_user']);
    }

    public function test_asset_show_omits_components_without_component_view_permission(): void
    {
        $asset = Asset::factory()->create();
        $component = Component::factory()->create(['name' => 'Hidden component']);
        $asset->components()->attach($component->id, [
            'assigned_qty' => 2,
            'created_by' => User::factory()->superuser()->create()->id,
        ]);

        $response = $this->actingAsForApi(User::factory()->viewAssets()->create())
            ->getJson(route('api.assets.show', [
                'hardware' => $asset->id,
                'components' => 'true',
            ]))
            ->assertOk();

        $this->assertSame([], $response->json('components'));
    }

    public function test_asset_show_keeps_components_with_component_view_permission(): void
    {
        $asset = Asset::factory()->create();
        $component = Component::factory()->create(['name' => 'Visible component']);
        $asset->components()->attach($component->id, [
            'assigned_qty' => 2,
            'created_by' => User::factory()->superuser()->create()->id,
        ]);
        $actor = User::factory()->viewAssets()->viewComponents()->create();

        $this->actingAsForApi($actor)
            ->getJson(route('api.assets.show', [
                'hardware' => $asset->id,
                'components' => 'true',
            ]))
            ->assertOk()
            ->assertJsonPath('components.0.id', $component->id)
            ->assertJsonPath('components.0.qty', 2);
    }
}
