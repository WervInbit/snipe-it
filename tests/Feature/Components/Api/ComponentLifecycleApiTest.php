<?php

namespace Tests\Feature\Components\Api;

use App\Models\Asset;
use App\Models\AttributeDefinition;
use App\Models\ComponentInstance;
use App\Models\User;
use App\Services\ComponentLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComponentLifecycleApiTest extends TestCase
{
    use RefreshDatabase;

    public function testUpdateEndpointRejectsLifecycleFieldMutations(): void
    {
        $user = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $component = ComponentInstance::factory()->installed($asset->id)->create([
            'installed_as' => 'Original Slot',
        ]);

        $this->actingAsForApi($user)
            ->putJson(route('api.components.update', $component), [
                'status' => ComponentInstance::STATUS_IN_STOCK,
                'current_asset_id' => null,
                'installed_as' => 'Changed Slot',
            ])
            ->assertStatus(422);

        $component->refresh();
        $this->assertSame(ComponentInstance::STATUS_INSTALLED, $component->status);
        $this->assertSame($asset->id, $component->current_asset_id);
        $this->assertSame('Original Slot', $component->installed_as);
    }

    public function testUpdateEndpointSyncsInstanceAttributes(): void
    {
        $user = User::factory()->superuser()->create();
        $component = ComponentInstance::factory()->create([
            'display_name' => 'API Memory Module',
        ]);
        $capacity = AttributeDefinition::create([
            'key' => 'ram_capacity_gb',
            'label' => 'RAM Capacity',
            'datatype' => AttributeDefinition::DATATYPE_INT,
        ]);

        $this->actingAsForApi($user)
            ->putJson(route('api.components.update', $component), [
                'display_name' => 'API Memory Module',
                'instance_attributes' => [
                    [
                        'attribute_definition_id' => $capacity->id,
                        'value' => '32',
                        'resolves_to_spec' => true,
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('payload.instance_attributes.0.key', 'ram_capacity_gb')
            ->assertJsonPath('payload.instance_attributes.0.value', '32')
            ->assertJsonPath('payload.instance_attributes.0.resolves_to_spec', true);

        $this->assertDatabaseHas('component_instance_attributes', [
            'component_instance_id' => $component->id,
            'attribute_definition_id' => $capacity->id,
            'value' => '32',
            'resolves_to_spec' => 1,
        ]);
    }

    public function testRemoveToTrayAlwaysAssignsTheActingUser(): void
    {
        $actor = User::factory()->superuser()->create();
        $otherUser = User::factory()->create();
        $asset = Asset::factory()->create();
        $component = ComponentInstance::factory()->installed($asset->id)->create([
            'source_asset_id' => $asset->id,
        ]);

        $this->actingAsForApi($actor)
            ->postJson(route('api.components.remove_to_tray', $component), [
                'held_by_user_id' => $otherUser->id,
                'note' => 'Removed for intake',
            ])
            ->assertOk();

        $component->refresh();
        $this->assertSame(ComponentInstance::STATUS_IN_TRANSFER, $component->status);
        $this->assertSame($actor->id, $component->held_by_user_id);
        $this->assertDatabaseHas('component_events', [
            'component_instance_id' => $component->id,
            'event_type' => 'removed_to_tray',
            'held_by_user_id' => $actor->id,
        ]);
    }

    public function testInstallEndpointRejectsAnotherUsersTrayComponent(): void
    {
        $holder = User::factory()->superuser()->create();
        $installer = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $component = ComponentInstance::factory()->inTray($holder)->create();

        $this->actingAsForApi($installer)
            ->postJson(route('api.components.install', $component), [
                'asset_id' => $asset->id,
            ])
            ->assertStatus(422);

        $component->refresh();
        $this->assertSame(ComponentInstance::STATUS_IN_TRANSFER, $component->status);
        $this->assertNull($component->current_asset_id);
        $this->assertSame($holder->id, $component->held_by_user_id);
        $this->assertDatabaseMissing('component_events', [
            'component_instance_id' => $component->id,
            'event_type' => 'installed',
            'to_asset_id' => $asset->id,
        ]);
    }

    public function testMarkDestroyedEndpointRequiresPendingStateAndEvidence(): void
    {
        $actor = User::factory()->superuser()->create();
        $component = ComponentInstance::factory()->create();

        $this->actingAsForApi($actor)
            ->postJson(route('api.components.mark_destroyed', $component), [
                'note' => 'Destroyed with verification.',
            ])
            ->assertStatus(422);

        $component->refresh();
        $this->assertSame(ComponentInstance::STATUS_IN_STOCK, $component->status);

        app(ComponentLifecycleService::class)->markDestructionPending($component, null, [
            'performed_by' => $actor,
        ]);

        $this->actingAsForApi($actor)
            ->postJson(route('api.components.mark_destroyed', $component->fresh()))
            ->assertStatus(422);

        $component->refresh();
        $this->assertSame(ComponentInstance::STATUS_DESTRUCTION_PENDING, $component->status);
    }
}
