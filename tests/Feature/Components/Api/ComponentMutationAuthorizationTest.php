<?php

namespace Tests\Feature\Components\Api;

use App\Models\Asset;
use App\Models\AttributeDefinition;
use App\Models\Company;
use App\Models\ComponentDefinition;
use App\Models\ComponentEvent;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use App\Models\Location;
use App\Models\User;
use Tests\TestCase;

class ComponentMutationAuthorizationTest extends TestCase
{
    public function testStoreRejectsEveryCrossCompanyReferenceWithoutMutation(): void
    {
        [$companyA, $companyB] = Company::factory()->count(2)->create();
        $actor = $companyA->users()->save(
            User::factory()
                ->createComponents()
                ->moveComponents()
                ->installComponents()
                ->verifyComponents()
                ->make()
        );
        $otherHolder = $companyB->users()->save(User::factory()->make());
        $otherAsset = Asset::factory()->create(['company_id' => $companyB->id]);
        $otherSite = Location::factory()->create(['company_id' => $companyB->id]);
        $otherStorage = ComponentStorageLocation::factory()->stock()->create([
            'site_location_id' => $otherSite->id,
        ]);

        $this->settings->enableMultipleFullCompanySupport();

        $initialComponentCount = ComponentInstance::withoutGlobalScopes()->count();
        $initialEventCount = ComponentEvent::query()->count();

        $requests = [
            [
                'payload' => [
                    'display_name' => 'Wrong company',
                    'company_id' => $companyB->id,
                    'condition_code' => ComponentInstance::CONDITION_GOOD,
                ],
                'field' => 'company_id',
            ],
            [
                'payload' => [
                    'display_name' => 'Wrong asset',
                    'company_id' => $companyA->id,
                    'lifecycle_status' => ComponentInstance::LIFECYCLE_ATTACHED,
                    'current_asset_id' => $otherAsset->id,
                    'condition_code' => ComponentInstance::CONDITION_GOOD,
                ],
                'field' => 'current_asset_id',
            ],
            [
                'payload' => [
                    'display_name' => 'Wrong source',
                    'company_id' => $companyA->id,
                    'source_asset_id' => $otherAsset->id,
                    'condition_code' => ComponentInstance::CONDITION_GOOD,
                ],
                'field' => 'source_asset_id',
            ],
            [
                'payload' => [
                    'display_name' => 'Wrong holder',
                    'company_id' => $companyA->id,
                    'lifecycle_status' => ComponentInstance::LIFECYCLE_IN_TRAY,
                    'held_by_user_id' => $otherHolder->id,
                    'condition_code' => ComponentInstance::CONDITION_GOOD,
                ],
                'field' => 'held_by_user_id',
            ],
            [
                'payload' => [
                    'display_name' => 'Wrong storage',
                    'company_id' => $companyA->id,
                    'storage_location_id' => $otherStorage->id,
                    'condition_code' => ComponentInstance::CONDITION_GOOD,
                ],
                'field' => 'storage_location_id',
            ],
        ];

        foreach ($requests as $request) {
            $this->actingAsForApi($actor)
                ->postJson(route('api.components.store'), $request['payload'])
                ->assertStatus(422)
                ->assertMessagesContains($request['field']);
        }

        $this->assertSame($initialComponentCount, ComponentInstance::withoutGlobalScopes()->count());
        $this->assertSame($initialEventCount, ComponentEvent::query()->count());
    }

    public function testStoreRejectsInactiveDefinitionWithoutMutation(): void
    {
        $actor = User::factory()
            ->createComponents()
            ->moveComponents()
            ->create();
        $definition = ComponentDefinition::factory()->create(['is_active' => false]);

        $this->actingAsForApi($actor)
            ->postJson(route('api.components.store'), [
                'component_definition_id' => $definition->id,
                'condition_code' => ComponentInstance::CONDITION_GOOD,
            ])
            ->assertStatus(422)
            ->assertMessagesContains('component_definition_id');

        $this->assertDatabaseCount('component_instances', 0);
        $this->assertDatabaseCount('component_events', 0);
    }

    public function testLifecycleEndpointsRejectCrossCompanyStorageWithoutMutation(): void
    {
        [$companyA, $companyB] = Company::factory()->count(2)->create();
        $actor = $companyA->users()->save(
            User::factory()
                ->moveComponents()
                ->installComponents()
                ->destroyComponents()
                ->make()
        );
        $otherAsset = Asset::factory()->create(['company_id' => $companyB->id]);
        $otherSite = Location::factory()->create(['company_id' => $companyB->id]);
        $otherStorage = ComponentStorageLocation::factory()->stock()->create([
            'site_location_id' => $otherSite->id,
        ]);
        $otherDestruction = ComponentStorageLocation::factory()->destruction()->create([
            'site_location_id' => $otherSite->id,
        ]);
        $component = ComponentInstance::factory()->inTray($actor)->create([
            'company_id' => $companyA->id,
        ]);

        $this->settings->enableMultipleFullCompanySupport();

        $this->actingAsForApi($actor)
            ->postJson(route('api.components.install', $component), [
                'asset_id' => $otherAsset->id,
            ])
            ->assertStatus(422)
            ->assertMessagesContains('asset_id');

        $this->actingAsForApi($actor)
            ->postJson(route('api.components.move_to_stock', $component), [
                'storage_location_id' => $otherStorage->id,
            ])
            ->assertStatus(422)
            ->assertMessagesContains('storage_location_id');

        $this->actingAsForApi($actor)
            ->postJson(route('api.components.mark_destruction_pending', $component), [
                'storage_location_id' => $otherDestruction->id,
                'note' => 'Must not cross company scope.',
            ])
            ->assertStatus(422)
            ->assertMessagesContains('storage_location_id');

        $component->refresh();
        $this->assertSame(ComponentInstance::LIFECYCLE_IN_TRAY, $component->lifecycle_status);
        $this->assertSame($actor->id, $component->held_by_user_id);
        $this->assertDatabaseMissing('component_events', [
            'component_instance_id' => $component->id,
            'event_type' => 'installed',
        ]);
        $this->assertDatabaseMissing('component_events', [
            'component_instance_id' => $component->id,
            'event_type' => 'moved_to_stock',
        ]);
        $this->assertDatabaseMissing('component_events', [
            'component_instance_id' => $component->id,
            'event_type' => 'marked_destruction_pending',
        ]);
    }

    public function testCreatePermissionAloneCannotChooseAStockLifecycleState(): void
    {
        $actor = User::factory()->createComponents()->create();

        $this->actingAsForApi($actor)
            ->postJson(route('api.components.store'), [
                'display_name' => 'Unauthorized stock component',
                'condition_code' => ComponentInstance::CONDITION_GOOD,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('component_instances', 0);
        $this->assertDatabaseCount('component_events', 0);
    }

    public function testNonGoodInitialConditionAlsoRequiresVerificationPermission(): void
    {
        $actor = User::factory()
            ->createComponents()
            ->moveComponents()
            ->create();

        $this->actingAsForApi($actor)
            ->postJson(route('api.components.store'), [
                'display_name' => 'Needs verification',
                'condition_code' => ComponentInstance::CONDITION_UNKNOWN,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('component_instances', 0);
        $this->assertDatabaseCount('component_events', 0);
    }

    public function testAttachedInitialStateRequiresInstallPermission(): void
    {
        $actor = User::factory()
            ->createComponents()
            ->moveComponents()
            ->verifyComponents()
            ->create();
        $asset = Asset::factory()->create();

        $this->actingAsForApi($actor)
            ->postJson(route('api.components.store'), [
                'display_name' => 'Unauthorized installed component',
                'lifecycle_status' => ComponentInstance::LIFECYCLE_ATTACHED,
                'current_asset_id' => $asset->id,
                'condition_code' => ComponentInstance::CONDITION_GOOD,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('component_instances', 0);
        $this->assertDatabaseCount('component_events', 0);
    }

    public function testAuthorizedAttachedCreateUsesLifecycleServiceAndWritesInstallEvent(): void
    {
        $actor = User::factory()
            ->createComponents()
            ->installComponents()
            ->create();
        $asset = Asset::factory()->create();

        $response = $this->actingAsForApi($actor)
            ->postJson(route('api.components.store'), [
                'display_name' => 'Installed through API',
                'lifecycle_status' => ComponentInstance::LIFECYCLE_ATTACHED,
                'current_asset_id' => $asset->id,
                'installed_as' => 'Bay 1',
                'condition_code' => ComponentInstance::CONDITION_GOOD,
            ])
            ->assertOk()
            ->assertStatusMessageIs('success')
            ->assertJsonPath('payload.lifecycle_status', ComponentInstance::LIFECYCLE_ATTACHED)
            ->assertJsonPath('payload.current_asset.id', $asset->id);

        $componentId = (int) $response->json('payload.id');

        $this->assertDatabaseHas('component_events', [
            'component_instance_id' => $componentId,
            'event_type' => 'created',
        ]);
        $this->assertDatabaseHas('component_events', [
            'component_instance_id' => $componentId,
            'event_type' => 'installed',
            'to_asset_id' => $asset->id,
        ]);
    }

    public function testAuthorizedDestroyedCreatePreservesDestructionSequenceAndEvidenceRule(): void
    {
        $actor = User::factory()
            ->createComponents()
            ->destroyComponents()
            ->create();

        $response = $this->actingAsForApi($actor)
            ->postJson(route('api.components.store'), [
                'display_name' => 'Historical destroyed part',
                'lifecycle_status' => ComponentInstance::LIFECYCLE_DESTROYED,
                'condition_code' => ComponentInstance::CONDITION_GOOD,
                'notes' => 'Destruction certificate checked.',
            ])
            ->assertOk()
            ->assertStatusMessageIs('success')
            ->assertJsonPath('payload.lifecycle_status', ComponentInstance::LIFECYCLE_DESTROYED);

        $componentId = (int) $response->json('payload.id');

        $this->assertSame([
            'created',
            'marked_destruction_pending',
            'destroyed_recycled',
        ], ComponentEvent::query()
            ->where('component_instance_id', $componentId)
            ->orderBy('id')
            ->pluck('event_type')
            ->all());
    }

    public function testMovePermissionDoesNotGrantDestructionAuthority(): void
    {
        $actor = User::factory()
            ->createComponents()
            ->moveComponents()
            ->create();
        $component = ComponentInstance::factory()->create();

        $this->actingAsForApi($actor)
            ->postJson(route('api.components.store'), [
                'display_name' => 'Unauthorized terminal part',
                'lifecycle_status' => ComponentInstance::LIFECYCLE_DESTROYED,
                'condition_code' => ComponentInstance::CONDITION_GOOD,
                'notes' => 'Should not be accepted.',
            ])
            ->assertForbidden();

        $this->actingAsForApi($actor)
            ->postJson(route('api.components.mark_destruction_pending', $component), [
                'note' => 'Should not be accepted.',
            ])
            ->assertForbidden();

        $component->refresh();
        $this->assertSame(ComponentInstance::LIFECYCLE_IN_STOCK, $component->lifecycle_status);
        $this->assertDatabaseMissing('component_events', [
            'component_instance_id' => $component->id,
            'event_type' => 'marked_destruction_pending',
        ]);
    }

    public function testStoreRejectsInconsistentLifecycleAndPlacementCombinationsWithoutMutation(): void
    {
        $actor = User::factory()
            ->createComponents()
            ->moveComponents()
            ->installComponents()
            ->verifyComponents()
            ->create();
        $asset = Asset::factory()->create();
        $storage = ComponentStorageLocation::factory()->stock()->create();

        $requests = [
            [
                'payload' => [
                    'display_name' => 'Conflicting status',
                    'status' => ComponentInstance::STATUS_INSTALLED,
                    'lifecycle_status' => ComponentInstance::LIFECYCLE_IN_STOCK,
                    'condition_code' => ComponentInstance::CONDITION_GOOD,
                ],
                'field' => 'lifecycle_status',
            ],
            [
                'payload' => [
                    'display_name' => 'Attached without asset',
                    'lifecycle_status' => ComponentInstance::LIFECYCLE_ATTACHED,
                    'storage_location_id' => $storage->id,
                    'condition_code' => ComponentInstance::CONDITION_GOOD,
                ],
                'field' => 'current_asset_id',
            ],
            [
                'payload' => [
                    'display_name' => 'Stock on asset',
                    'lifecycle_status' => ComponentInstance::LIFECYCLE_IN_STOCK,
                    'current_asset_id' => $asset->id,
                    'condition_code' => ComponentInstance::CONDITION_GOOD,
                ],
                'field' => 'current_asset_id',
            ],
            [
                'payload' => [
                    'display_name' => 'Contradictory condition',
                    'status' => ComponentInstance::STATUS_NEEDS_VERIFICATION,
                    'condition_code' => ComponentInstance::CONDITION_GOOD,
                ],
                'field' => 'condition_status',
            ],
        ];

        foreach ($requests as $request) {
            $this->actingAsForApi($actor)
                ->postJson(route('api.components.store'), $request['payload'])
                ->assertStatus(422)
                ->assertMessagesContains($request['field']);
        }

        $this->assertDatabaseCount('component_instances', 0);
        $this->assertDatabaseCount('component_events', 0);
    }

    public function testSerialUpdateUsesAuditedLifecycleService(): void
    {
        $actor = User::factory()->updateComponents()->create();
        $component = ComponentInstance::factory()->create([
            'serial' => 'SERIAL-BEFORE',
        ]);

        $this->actingAsForApi($actor)
            ->putJson(route('api.components.update', $component), [
                'serial' => 'SERIAL-AFTER',
            ])
            ->assertOk()
            ->assertStatusMessageIs('success')
            ->assertJsonPath('payload.serial', 'SERIAL-AFTER');

        $event = ComponentEvent::query()
            ->where('component_instance_id', $component->id)
            ->where('event_type', 'serial_updated')
            ->sole();

        $this->assertSame('SERIAL-BEFORE', $event->payload_json['previous_serial']);
        $this->assertSame('SERIAL-AFTER', $event->payload_json['new_serial']);
        $this->assertSame($actor->id, $event->performed_by);
    }

    public function testTerminalComponentSerialAndMetadataCannotMutate(): void
    {
        $actor = User::factory()->updateComponents()->create();
        $component = ComponentInstance::factory()->create([
            'display_name' => 'Destroyed part',
            'serial' => 'TERMINAL-SERIAL',
            'status' => ComponentInstance::STATUS_DESTROYED_RECYCLED,
            'lifecycle_status' => ComponentInstance::LIFECYCLE_DESTROYED,
            'storage_location_id' => null,
            'destroyed_at' => now(),
        ]);

        $this->actingAsForApi($actor)
            ->putJson(route('api.components.update', $component), [
                'display_name' => 'Should roll back',
                'serial' => 'MUTATED-SERIAL',
            ])
            ->assertStatus(422)
            ->assertStatusMessageIs('error');

        $component->refresh();
        $this->assertSame('Destroyed part', $component->display_name);
        $this->assertSame('TERMINAL-SERIAL', $component->serial);
        $this->assertDatabaseMissing('component_events', [
            'component_instance_id' => $component->id,
            'event_type' => 'serial_updated',
        ]);
    }

    public function testFailedAttributeSyncRollsBackSerialMetadataAndAuditEvent(): void
    {
        $actor = User::factory()->updateComponents()->create();
        $component = ComponentInstance::factory()->create([
            'display_name' => 'Original name',
            'serial' => 'ROLLBACK-BEFORE',
        ]);
        $attribute = AttributeDefinition::create([
            'key' => 'valid_attribute',
            'label' => 'Valid Attribute',
            'datatype' => AttributeDefinition::DATATYPE_TEXT,
        ]);

        $this->actingAsForApi($actor)
            ->putJson(route('api.components.update', $component), [
                'display_name' => 'Should not persist',
                'serial' => 'ROLLBACK-AFTER',
                'instance_attributes' => [
                    [
                        'attribute_definition_id' => $attribute->id,
                        'value' => 'first',
                    ],
                    [
                        'attribute_definition_id' => $attribute->id,
                        'value' => 'duplicate',
                    ],
                ],
            ])
            ->assertStatus(422)
            ->assertStatusMessageIs('error');

        $component->refresh();
        $this->assertSame('Original name', $component->display_name);
        $this->assertSame('ROLLBACK-BEFORE', $component->serial);
        $this->assertDatabaseMissing('component_events', [
            'component_instance_id' => $component->id,
            'event_type' => 'serial_updated',
        ]);
        $this->assertDatabaseMissing('component_instance_attributes', [
            'component_instance_id' => $component->id,
        ]);
    }
}
