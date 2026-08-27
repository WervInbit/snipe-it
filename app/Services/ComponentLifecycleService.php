<?php

namespace App\Services;

use App\Exceptions\ComponentConditionWarningException;
use App\Exceptions\ComponentLifecycleWarningException;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\CompanyableScope;
use App\Models\ComponentDefinition;
use App\Models\ComponentDefinitionSubcomponentTemplate;
use App\Models\ComponentEvent;
use App\Models\ComponentExpectedSubcomponentState;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use App\Models\Location;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ComponentLifecycleService
{
    public function __construct(
        protected ComponentEventWriter $events,
    ) {
    }

    public function createInstance(array $attributes, User|int|null $performedBy = null): ComponentInstance
    {
        return DB::transaction(function () use ($attributes, $performedBy): ComponentInstance {
            $actorId = $this->resolveActorId($performedBy);
            $normalizedAttributes = $this->normalizeInstanceAttributes($attributes, $actorId);
            $this->lockLiveParentForChildCreation($normalizedAttributes);
            $definition = $this->activeDefinitionForInstanceAttributes($normalizedAttributes);
            $this->assertPlacementAllowedForInstanceAttributes($normalizedAttributes, $definition);
            $this->assertCompanyConsistencyForInstanceAttributes($normalizedAttributes);
            $this->assertSerialMatchesDefinition($definition, $normalizedAttributes['serial'] ?? null);

            $instance = new ComponentInstance(array_merge([
                'created_by' => $attributes['created_by'] ?? $actorId,
                'updated_by' => $attributes['updated_by'] ?? $actorId,
            ], $normalizedAttributes));
            $instance->save();

            $this->events->write($instance, 'created', [
                'performed_by' => $performedBy,
                'to_status' => $instance->status,
                'to_asset_id' => $instance->current_asset_id,
                'to_storage_location_id' => $instance->storage_location_id,
                'held_by_user_id' => $instance->held_by_user_id,
                'note' => $attributes['notes'] ?? null,
                'payload_json' => $attributes['metadata_json'] ?? null,
            ]);

            return $instance->fresh([
                'componentDefinition',
                'sourceAsset',
                'currentAsset',
                'rootAsset',
                'parentComponent',
                'storageLocation',
                'heldBy',
            ]);
        });
    }

    public function extractFromAsset(
        Asset $asset,
        array $attributes,
        User|int|null $holder = null,
        ?WorkOrder $workOrder = null,
        ?WorkOrderTask $task = null,
    ): ComponentInstance {
        $holderId = $holder instanceof User ? $holder->id : $holder;

            $instance = $this->createInstance(array_merge($attributes, [
                'source_type' => ComponentInstance::SOURCE_EXTRACTED,
                'company_id' => $attributes['company_id'] ?? $asset->company_id,
                'source_asset_id' => $attributes['source_asset_id'] ?? $asset->id,
            'current_asset_id' => null,
            'parent_component_instance_id' => null,
            'root_asset_id' => null,
            'storage_location_id' => null,
                'held_by_user_id' => $holderId,
                'transfer_started_at' => $attributes['transfer_started_at'] ?? now(),
                'status' => ComponentInstance::STATUS_IN_TRANSFER,
                'lifecycle_status' => ComponentInstance::LIFECYCLE_IN_TRAY,
                'display_name' => $attributes['display_name'] ?? 'Extracted component',
            ]), $holder);

        $this->events->write($instance, 'extracted', [
            'performed_by' => $holder,
            'to_status' => ComponentInstance::STATUS_IN_TRANSFER,
            'from_asset_id' => $asset->id,
            'held_by_user_id' => $holderId,
            'related_work_order_id' => $workOrder?->id,
            'related_work_order_task_id' => $task?->id,
            'note' => $attributes['event_note'] ?? null,
        ]);

        return $instance->fresh();
    }

    public function removeToTray(
        ComponentInstance $instance,
        User|int $holder,
        array $context = [],
    ): ComponentInstance {
        $this->assertNotTerminal($instance);

        return DB::transaction(function () use ($instance, $holder, $context): ComponentInstance {
            $detachedChild = $this->prepareDetachedChildSnapshot($instance);
            $attachedChildren = $this->attachedChildrenForParentMove($instance);
            $holderId = $holder instanceof User ? $holder->id : $holder;
            $fromAssetId = $instance->current_asset_id;
            $fromStatus = $instance->status;
            $fromStorageLocationId = $instance->storage_location_id;
            $fromSerial = $this->normalizeComponentSerial($instance->serial);

            $instance->forceFill(array_merge([
                'status' => ComponentInstance::STATUS_IN_TRANSFER,
                'lifecycle_status' => ComponentInstance::LIFECYCLE_IN_TRAY,
                'source_asset_id' => $instance->source_asset_id ?? $fromAssetId,
                'current_asset_id' => null,
                'parent_component_instance_id' => null,
                'root_asset_id' => null,
                'storage_location_id' => null,
                'held_by_user_id' => $holderId,
                'transfer_started_at' => $context['transfer_started_at'] ?? now(),
                'updated_by' => $holderId,
            ], $this->serialUpdateAttributes($context), $this->detachedChildInstanceAttributes($detachedChild)))->save();

            $payload = $this->mergeSerialChangePayload(
                $context['payload_json'] ?? null,
                $fromSerial,
                $this->normalizeComponentSerial($instance->serial)
            );

            $event = $this->events->write($instance, 'removed_to_tray', [
                'performed_by' => $holder,
                'from_status' => $fromStatus,
                'to_status' => ComponentInstance::STATUS_IN_TRANSFER,
                'from_asset_id' => $fromAssetId,
                'from_storage_location_id' => $fromStorageLocationId,
                'held_by_user_id' => $holderId,
                'related_work_order_id' => $context['related_work_order_id'] ?? null,
                'related_work_order_task_id' => $context['related_work_order_task_id'] ?? null,
                'note' => $context['note'] ?? null,
                'payload_json' => $this->mergeAttachedChildMovePayload(
                    $attachedChildren,
                    $this->mergeDetachedChildPayload($detachedChild, $payload)
                ),
            ]);

            $this->completeDetachedChildEventSnapshot($instance, $detachedChild, $event);
            $this->moveAttachedChildrenOffAssetWithParent($instance, $attachedChildren, $context, $event);

            return $instance->fresh();
        });
    }

    public function installIntoAsset(
        ComponentInstance $instance,
        Asset $asset,
        array $context = [],
    ): ComponentInstance {
        $targetCompanyId = $context['company_id'] ?? $asset->company_id ?? $instance->company_id;
        if ($targetCompanyId) {
            $this->assertActorCanUseCompanyScope(
                $this->resolveActorId($context['performed_by'] ?? null),
                (int) $targetCompanyId
            );
        }

        if ($asset->company_id
            && $targetCompanyId
            && (int) $asset->company_id !== (int) $targetCompanyId
        ) {
            throw new InvalidArgumentException('Component company must match the destination asset company.');
        }

        $this->assertCanInstall($instance);
        $this->assertComponentDefinitionCanBeInstalledOnAsset($instance);
        $this->assertLifecycleWarningConfirmed($instance, $context);
        $this->assertConditionWarningConfirmed($instance, $context);
        $this->assertTrayHolderCanInstall($instance, $context['performed_by'] ?? null);

        return DB::transaction(function () use ($instance, $asset, $context): ComponentInstance {
            $detachedChild = $this->prepareDetachedChildSnapshot($instance);
            $attachedChildren = $this->attachedChildrenForParentMove($instance);
            $fromStatus = $instance->status;
            $fromAssetId = $instance->current_asset_id;
            $fromStorageLocationId = $instance->storage_location_id;
            $heldByUserId = $instance->held_by_user_id;

            $instance->forceFill(array_merge([
                'status' => ComponentInstance::STATUS_INSTALLED,
                'lifecycle_status' => ComponentInstance::LIFECYCLE_ATTACHED,
                'company_id' => $context['company_id'] ?? $asset->company_id ?? $instance->company_id,
                'current_asset_id' => $asset->id,
                'parent_component_instance_id' => null,
                'root_asset_id' => $asset->id,
                'storage_location_id' => null,
                'held_by_user_id' => null,
                'transfer_started_at' => null,
                'installed_as' => $context['installed_as'] ?? $instance->installed_as,
                'last_verified_at' => $context['last_verified_at'] ?? $instance->last_verified_at,
                'updated_by' => $this->resolveActorId($context['performed_by'] ?? null),
            ], $this->detachedChildInstanceAttributes($detachedChild)))->save();

            $this->ensureInstanceCompanyId($instance);

            $event = $this->events->write($instance, 'installed', [
                'performed_by' => $context['performed_by'] ?? null,
                'from_status' => $fromStatus,
                'to_status' => ComponentInstance::STATUS_INSTALLED,
                'from_asset_id' => $fromAssetId,
                'to_asset_id' => $asset->id,
                'from_storage_location_id' => $fromStorageLocationId,
                'held_by_user_id' => $heldByUserId,
                'related_work_order_id' => $context['related_work_order_id'] ?? null,
                'related_work_order_task_id' => $context['related_work_order_task_id'] ?? null,
                'note' => $context['note'] ?? null,
                'payload_json' => $this->mergeAttachedChildMovePayload($attachedChildren, $this->mergeDetachedChildPayload($detachedChild, [
                    'installed_as' => $instance->installed_as,
                ])),
            ]);

            $this->completeDetachedChildEventSnapshot($instance, $detachedChild, $event);
            $this->moveAttachedChildrenWithParent($instance, $attachedChildren, $asset, $context, $event);

            return $instance->fresh();
        });
    }

    public function reparentWithinAsset(
        ComponentInstance $instance,
        ?ComponentInstance $parent = null,
        array $context = [],
    ): ComponentInstance {
        $instanceId = (int) $instance->getKey();
        $targetParentId = $parent?->getKey() ? (int) $parent->getKey() : null;

        return DB::transaction(function () use ($instanceId, $context, $targetParentId): ComponentInstance {
            $instance = ComponentInstance::withoutGlobalScope(CompanyableScope::class)
                ->whereKey($instanceId)
                ->lockForUpdate()
                ->first();

            if (!$instance) {
                throw new InvalidArgumentException('Component could not be found.');
            }

            $actorId = $this->resolveActorId($context['performed_by'] ?? null);
            if ($instance->company_id) {
                $this->assertActorCanUseCompanyScope($actorId, (int) $instance->company_id);
            }

            $fromParentId = $instance->parent_component_instance_id
                ? (int) $instance->parent_component_instance_id
                : null;
            $parentIds = array_values(array_unique(array_filter([
                $fromParentId,
                $targetParentId,
            ])));
            sort($parentIds);

            $lockedParents = ComponentInstance::withoutGlobalScope(CompanyableScope::class)
                ->whereIn('id', $parentIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            /** @var ComponentInstance|null $parent */
            $parent = $targetParentId ? $lockedParents->get($targetParentId) : null;
            if ($targetParentId && !$parent) {
                throw new InvalidArgumentException('Parent component could not be found.');
            }

            $this->assertCanReparentWithinAsset($instance, $parent);

            if (($fromParentId ?? 0) === ($targetParentId ?? 0)) {
                return $instance->fresh();
            }

            if ($fromParentId && !$lockedParents->has($fromParentId)) {
                throw new InvalidArgumentException('The current parent component could not be found.');
            }

            $expectedStateChanges = $this->reconcileExpectedSubcomponentReparent(
                $instance,
                $fromParentId ? $lockedParents->get($fromParentId) : null,
                $parent
            );
            $assetId = (int) $instance->current_asset_id;
            $metadata = $this->reparentedMetadata($instance, $fromParentId, $targetParentId);

            $instance->forceFill(array_filter([
                'parent_component_instance_id' => $targetParentId,
                'root_asset_id' => $parent ? ($parent->root_asset_id ?: $parent->current_asset_id) : $assetId,
                'updated_by' => $actorId,
                'metadata_json' => $metadata,
            ], fn ($value, $key) => $key !== 'metadata_json' || $value !== null, ARRAY_FILTER_USE_BOTH))->save();

            $this->events->write($instance, 'reparented', [
                'performed_by' => $context['performed_by'] ?? null,
                'from_status' => $instance->status,
                'to_status' => $instance->status,
                'from_asset_id' => $assetId,
                'to_asset_id' => $assetId,
                'note' => $context['note'] ?? null,
                'payload_json' => [
                    'from_parent_component_instance_id' => $fromParentId,
                    'to_parent_component_instance_id' => $targetParentId,
                    'expected_subcomponent_state_changes' => $expectedStateChanges ?: null,
                ],
            ]);

            return $instance->fresh();
        });
    }

    /**
     * Soft deletion is only safe once an assembly has no live children.
     * Lifecycle moves intentionally keep children linked to their parent, even
     * while the complete assembly is off an asset, so those children must be
     * detached or deleted explicitly before the parent can disappear.
     */
    public function deleteInstance(
        ComponentInstance $instance,
        User|int|null $performedBy = null,
        array $context = [],
    ): ComponentInstance {
        $instanceId = (int) $instance->getKey();

        return DB::transaction(function () use ($instanceId, $performedBy, $context): ComponentInstance {
            $instance = ComponentInstance::withoutGlobalScope(CompanyableScope::class)
                ->whereKey($instanceId)
                ->lockForUpdate()
                ->first();

            if (!$instance) {
                throw new InvalidArgumentException('Component could not be found.');
            }

            $actorId = $this->resolveActorId($performedBy);
            if ($instance->company_id) {
                $this->assertActorCanUseCompanyScope($actorId, (int) $instance->company_id);
            }

            if ($instance->effectiveLifecycleStatus() === ComponentInstance::LIFECYCLE_ATTACHED) {
                throw new InvalidArgumentException('Installed components must be removed before deletion.');
            }

            $children = ComponentInstance::withoutGlobalScope(CompanyableScope::class)
                ->where('parent_component_instance_id', $instance->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($children->isNotEmpty()) {
                throw new InvalidArgumentException(
                    'Components with child components cannot be deleted. Detach or delete the child components first.'
                );
            }

            $expectedStateChanges = $this->reconcileExpectedSubcomponentDeletion($instance);

            $this->events->write($instance, 'deleted', [
                'performed_by' => $performedBy,
                'from_status' => $instance->status,
                'from_asset_id' => $instance->current_asset_id,
                'from_storage_location_id' => $instance->storage_location_id,
                'held_by_user_id' => $instance->held_by_user_id,
                'note' => $context['note'] ?? null,
                'payload_json' => array_filter([
                    'parent_component_instance_id' => $instance->parent_component_instance_id,
                    'expected_subcomponent_state_changes' => $expectedStateChanges ?: null,
                ], fn ($value) => $value !== null),
            ]);

            $this->writeDeleteActionLog($instance, $actorId, $context['note'] ?? null);

            if (!$instance->delete()) {
                throw new \RuntimeException('Component deletion could not be persisted.');
            }

            return $instance;
        });
    }

    public function updateSerial(ComponentInstance $instance, ?string $serial, array $context = []): ComponentInstance
    {
        $this->assertNotTerminal($instance);
        $instance->loadMissing('componentDefinition');
        $this->assertSerialMatchesDefinition($instance->componentDefinition, $serial);

        return DB::transaction(function () use ($instance, $serial, $context): ComponentInstance {
            $fromSerial = $this->normalizeComponentSerial($instance->serial);
            $toSerial = $this->normalizeComponentSerial($serial);

            if ($fromSerial === $toSerial) {
                return $instance->fresh();
            }

            $fromStatus = $instance->status;

            $instance->forceFill([
                'serial' => $toSerial,
                'updated_by' => $this->resolveActorId($context['performed_by'] ?? null),
            ])->save();

            $this->events->write($instance, 'serial_updated', [
                'performed_by' => $context['performed_by'] ?? null,
                'from_status' => $fromStatus,
                'to_status' => $instance->status,
                'note' => $context['note'] ?? null,
                'payload_json' => $this->mergeSerialChangePayload(null, $fromSerial, $toSerial),
            ]);

            return $instance->fresh();
        });
    }

    /**
     * Update descriptive and procurement metadata without allowing lifecycle,
     * hierarchy, or immutable traceability fields to bypass their dedicated
     * actions.
     */
    public function updateMetadata(ComponentInstance $instance, array $attributes, array $context = []): ComponentInstance
    {
        $allowed = array_flip([
            'component_definition_id',
            'display_name',
            'supplier_id',
            'purchase_cost',
            'received_at',
            'metadata_json',
            'notes',
        ]);
        $attributes = array_intersect_key($attributes, $allowed);

        return DB::transaction(function () use ($instance, $attributes, $context): ComponentInstance {
            $locked = ComponentInstance::query()
                ->with('componentDefinition')
                ->lockForUpdate()
                ->findOrFail($instance->id);

            $this->assertNotTerminal($locked);

            if (array_key_exists('display_name', $attributes)) {
                $displayName = trim((string) ($attributes['display_name'] ?? ''));
                $attributes['display_name'] = $displayName !== '' ? $displayName : null;
            }

            if (array_key_exists('notes', $attributes)) {
                $notes = trim((string) ($attributes['notes'] ?? ''));
                $attributes['notes'] = $notes !== '' ? $notes : null;
            }

            if (array_key_exists('component_definition_id', $attributes)) {
                $definitionId = filled($attributes['component_definition_id'])
                    ? (int) $attributes['component_definition_id']
                    : null;
                $attributes['component_definition_id'] = $definitionId;

                $this->assertComponentDefinitionChangeIsSafe($locked, $definitionId);
            }

            $candidateDefinitionId = array_key_exists('component_definition_id', $attributes)
                ? $attributes['component_definition_id']
                : $locked->component_definition_id;
            $candidateDisplayName = array_key_exists('display_name', $attributes)
                ? $attributes['display_name']
                : $locked->getRawOriginal('display_name');

            if (!$candidateDefinitionId && blank($candidateDisplayName)) {
                throw new InvalidArgumentException('A component definition or free name is required.');
            }

            $candidateAttributes = array_merge($locked->getAttributes(), [
                'component_definition_id' => $candidateDefinitionId,
            ]);
            $this->assertPlacementAllowedForInstanceAttributes($candidateAttributes);

            $locked->fill($attributes);
            $locked->updated_by = $this->resolveActorId($context['performed_by'] ?? null);

            $changedFields = array_values(array_diff(
                array_keys($locked->getDirty()),
                ['updated_by', 'updated_at']
            ));

            if ($changedFields === []) {
                return $locked->fresh();
            }

            $locked->save();

            $this->events->write($locked, 'metadata_updated', [
                'performed_by' => $context['performed_by'] ?? null,
                'from_status' => $locked->status,
                'to_status' => $locked->status,
                'from_asset_id' => $locked->current_asset_id,
                'to_asset_id' => $locked->current_asset_id,
                'from_storage_location_id' => $locked->storage_location_id,
                'to_storage_location_id' => $locked->storage_location_id,
                'note' => $context['note'] ?? null,
                'payload_json' => ['changed_fields' => $changedFields],
            ]);

            return $locked->fresh();
        });
    }

    public function moveToStock(
        ComponentInstance $instance,
        ?ComponentStorageLocation $location = null,
        array $context = [],
    ): ComponentInstance {
        if ($location?->type === ComponentStorageLocation::TYPE_DESTRUCTION) {
            throw new InvalidArgumentException('Use markDestructionPending() for destruction locations.');
        }

        $this->assertNotTerminal($instance);
        $this->assertStorageLocationCompany($instance, $location);

        $updated = DB::transaction(function () use ($instance, $location, $context): ComponentInstance {
            $detachedChild = $this->prepareDetachedChildSnapshot($instance);
            $attachedChildren = $this->attachedChildrenForParentMove($instance);
            $fromStatus = $instance->status;
            $fromAssetId = $instance->current_asset_id;
            $fromStorageLocationId = $instance->storage_location_id;
            $heldByUserId = $instance->held_by_user_id;

            $instance->forceFill(array_merge([
                'status' => ComponentInstance::STATUS_IN_STOCK,
                'lifecycle_status' => ComponentInstance::LIFECYCLE_IN_STOCK,
                'current_asset_id' => null,
                'parent_component_instance_id' => null,
                'root_asset_id' => null,
                'storage_location_id' => $location?->id,
                'held_by_user_id' => null,
                'transfer_started_at' => null,
                'updated_by' => $this->resolveActorId($context['performed_by'] ?? null),
            ], $this->detachedChildInstanceAttributes($detachedChild)))->save();

            $event = $this->events->write($instance, 'moved_to_stock', [
                'performed_by' => $context['performed_by'] ?? null,
                'from_status' => $fromStatus,
                'to_status' => ComponentInstance::STATUS_IN_STOCK,
                'from_asset_id' => $fromAssetId,
                'from_storage_location_id' => $fromStorageLocationId,
                'to_storage_location_id' => $location?->id,
                'held_by_user_id' => $heldByUserId,
                'related_work_order_id' => $context['related_work_order_id'] ?? null,
                'related_work_order_task_id' => $context['related_work_order_task_id'] ?? null,
                'note' => $context['note'] ?? null,
                'payload_json' => $this->mergeAttachedChildMovePayload(
                    $attachedChildren,
                    $this->mergeDetachedChildPayload($detachedChild, $context['payload_json'] ?? null)
                ),
            ]);

            $this->completeDetachedChildEventSnapshot($instance, $detachedChild, $event);
            $this->moveAttachedChildrenOffAssetWithParent($instance, $attachedChildren, $context, $event);

            return $instance->fresh();
        });

        if (!empty($context['needs_verification'])) {
            return $this->flagNeedsVerification($updated, array_merge($context, [
                'storage_location' => $context['storage_location'] ?? $location,
            ]));
        }

        return $updated;
    }

    public function updateStorageLocation(
        ComponentInstance $instance,
        ?ComponentStorageLocation $location,
        array $context = [],
    ): ComponentInstance {
        $this->assertNotTerminal($instance);
        $this->assertStorageLocationCompany($instance, $location);

        if ($instance->effectiveLifecycleStatus() === ComponentInstance::LIFECYCLE_ATTACHED) {
            throw new InvalidArgumentException('Installed components do not have a storage location.');
        }

        if ($instance->effectiveLifecycleStatus() === ComponentInstance::LIFECYCLE_IN_TRAY) {
            throw new InvalidArgumentException('Tray components do not have a storage location.');
        }

        if ((int) ($instance->storage_location_id ?? 0) === (int) ($location?->id ?? 0)) {
            return $instance->fresh();
        }

        return DB::transaction(function () use ($instance, $location, $context): ComponentInstance {
            $fromStorageLocationId = $instance->storage_location_id;

            $instance->forceFill([
                'storage_location_id' => $location?->id,
                'updated_by' => $this->resolveActorId($context['performed_by'] ?? null),
            ])->save();

            $this->events->write($instance, 'storage_location_updated', [
                'performed_by' => $context['performed_by'] ?? null,
                'from_status' => $instance->status,
                'to_status' => $instance->status,
                'from_storage_location_id' => $fromStorageLocationId,
                'to_storage_location_id' => $location?->id,
                'note' => $context['note'] ?? null,
            ]);

            return $instance->fresh();
        });
    }

    public function flagNeedsVerification(ComponentInstance $instance, array $context = []): ComponentInstance
    {
        $this->assertNotTerminal($instance);

        return DB::transaction(function () use ($instance, $context): ComponentInstance {
            $location = $context['storage_location'] ?? $instance->storageLocation;
            $this->assertStorageLocationCompany($instance, $location);
            $fromStatus = $instance->status;
            $fromAssetId = $instance->current_asset_id;
            $fromConditionStatus = $instance->effectiveConditionStatus();
            $lifecycleStatus = $instance->effectiveLifecycleStatus();
            $targetStorageLocationId = $lifecycleStatus === ComponentInstance::LIFECYCLE_IN_STOCK
                ? $location?->id
                : null;
            $payload = is_array($context['payload_json'] ?? null) ? $context['payload_json'] : [];

            $attributes = [
                'condition_status' => ComponentInstance::CONDITION_STATUS_NEEDS_ATTENTION,
                'condition_code' => ComponentInstance::CONDITION_UNKNOWN,
                'needs_verification_at' => $context['needs_verification_at'] ?? now(),
                'updated_by' => $this->resolveActorId($context['performed_by'] ?? null),
            ];

            if ($lifecycleStatus === ComponentInstance::LIFECYCLE_IN_STOCK) {
                $attributes['storage_location_id'] = $targetStorageLocationId;
            }

            if (in_array($instance->status, [
                ComponentInstance::STATUS_NEEDS_VERIFICATION,
                ComponentInstance::STATUS_DEFECTIVE,
            ], true)) {
                $attributes['status'] = ComponentInstance::legacyStatusForLifecycleStatus($lifecycleStatus);
            }

            $instance->forceFill($attributes)->save();

            $this->events->write($instance, 'flagged_needs_verification', [
                'performed_by' => $context['performed_by'] ?? null,
                'from_status' => $fromStatus,
                'to_status' => $instance->status,
                'from_asset_id' => $fromAssetId,
                'to_storage_location_id' => $targetStorageLocationId,
                'related_work_order_id' => $context['related_work_order_id'] ?? null,
                'related_work_order_task_id' => $context['related_work_order_task_id'] ?? null,
                'note' => $context['note'] ?? null,
                'payload_json' => array_merge($payload, [
                    'from_condition_status' => $fromConditionStatus,
                    'to_condition_status' => ComponentInstance::CONDITION_STATUS_NEEDS_ATTENTION,
                ]),
            ]);

            return $instance->fresh();
        });
    }

    public function confirmVerification(
        ComponentInstance $instance,
        ?ComponentStorageLocation $location = null,
        array $context = [],
    ): ComponentInstance {
        $this->assertNotTerminal($instance);
        $this->assertStorageLocationCompany($instance, $location);

        return DB::transaction(function () use ($instance, $location, $context): ComponentInstance {
            $fromStatus = $instance->status;
            $fromConditionStatus = $instance->effectiveConditionStatus();
            $lifecycleStatus = $instance->effectiveLifecycleStatus();
            $targetLocationId = $location?->id ?? $instance->storage_location_id;

            $attributes = [
                'condition_status' => ComponentInstance::CONDITION_STATUS_GOOD,
                'condition_code' => ComponentInstance::CONDITION_GOOD,
                'last_verified_at' => $context['verified_at'] ?? now(),
                'needs_verification_at' => null,
                'updated_by' => $this->resolveActorId($context['performed_by'] ?? null),
            ];

            if ($lifecycleStatus === ComponentInstance::LIFECYCLE_IN_STOCK) {
                $attributes = array_merge($attributes, [
                    'status' => ComponentInstance::STATUS_IN_STOCK,
                    'lifecycle_status' => ComponentInstance::LIFECYCLE_IN_STOCK,
                    'current_asset_id' => null,
                    'parent_component_instance_id' => null,
                    'root_asset_id' => null,
                    'storage_location_id' => $targetLocationId,
                ]);
            } elseif ($instance->status === ComponentInstance::STATUS_NEEDS_VERIFICATION) {
                $attributes['status'] = ComponentInstance::legacyStatusForLifecycleStatus($lifecycleStatus);
            }

            $instance->forceFill($attributes)->save();

            $this->events->write($instance, 'verification_confirmed', [
                'performed_by' => $context['performed_by'] ?? null,
                'from_status' => $fromStatus,
                'to_status' => $instance->status,
                'to_storage_location_id' => $targetLocationId,
                'note' => $context['note'] ?? null,
                'payload_json' => [
                    'from_condition_status' => $fromConditionStatus,
                    'to_condition_status' => ComponentInstance::CONDITION_STATUS_GOOD,
                ],
            ]);

            return $instance->fresh();
        });
    }

    public function updateCondition(ComponentInstance $instance, string $conditionCode, array $context = []): ComponentInstance
    {
        $this->assertNotTerminal($instance);

        if (!array_key_exists($conditionCode, ComponentInstance::conditionCodeOptions())) {
            throw new InvalidArgumentException('Invalid component condition.');
        }

        return DB::transaction(function () use ($instance, $conditionCode, $context): ComponentInstance {
            $fromStatus = $instance->status;
            $fromConditionCode = $instance->condition_code;
            $fromConditionStatus = $instance->effectiveConditionStatus();
            $toConditionStatus = ComponentInstance::conditionStatusForConditionCode($conditionCode);
            $attributes = [
                'condition_code' => $conditionCode,
                'condition_status' => $toConditionStatus,
                'updated_by' => $this->resolveActorId($context['performed_by'] ?? null),
            ];

            if ($toConditionStatus === ComponentInstance::CONDITION_STATUS_NEEDS_ATTENTION) {
                $attributes['needs_verification_at'] = $context['needs_verification_at'] ?? $instance->needs_verification_at ?? now();
            } else {
                $attributes['needs_verification_at'] = null;
            }

            if (
                $instance->status === ComponentInstance::STATUS_NEEDS_VERIFICATION
                && $toConditionStatus !== ComponentInstance::CONDITION_STATUS_NEEDS_ATTENTION
            ) {
                $attributes['status'] = ComponentInstance::legacyStatusForLifecycleStatus($instance->effectiveLifecycleStatus());
            }

            $instance->forceFill($attributes)->save();

            $this->events->write($instance, 'condition_updated', [
                'performed_by' => $context['performed_by'] ?? null,
                'from_status' => $fromStatus,
                'to_status' => $instance->status,
                'from_asset_id' => $instance->current_asset_id,
                'to_asset_id' => $instance->current_asset_id,
                'from_storage_location_id' => $instance->storage_location_id,
                'to_storage_location_id' => $instance->storage_location_id,
                'note' => $context['note'] ?? null,
                'payload_json' => [
                    'from_condition_code' => $fromConditionCode,
                    'to_condition_code' => $conditionCode,
                    'from_condition_status' => $fromConditionStatus,
                    'to_condition_status' => $toConditionStatus,
                ],
            ]);

            return $instance->fresh();
        });
    }

    public function markDefective(ComponentInstance $instance, array $context = []): ComponentInstance
    {
        $this->assertNotTerminal($instance);

        return DB::transaction(function () use ($instance, $context): ComponentInstance {
            $fromStatus = $instance->status;
            $fromAssetId = $instance->current_asset_id;
            $fromStorageLocationId = $instance->storage_location_id;
            $heldByUserId = $instance->held_by_user_id;
            $fromConditionStatus = $instance->effectiveConditionStatus();
            $lifecycleStatus = $instance->effectiveLifecycleStatus();

            $attributes = [
                'condition_status' => ComponentInstance::CONDITION_STATUS_DAMAGED,
                'condition_code' => ComponentInstance::CONDITION_BROKEN,
                'updated_by' => $this->resolveActorId($context['performed_by'] ?? null),
            ];

            if (in_array($instance->status, [
                ComponentInstance::STATUS_NEEDS_VERIFICATION,
                ComponentInstance::STATUS_DEFECTIVE,
            ], true)) {
                $attributes['status'] = ComponentInstance::legacyStatusForLifecycleStatus($lifecycleStatus);
            }

            $instance->forceFill($attributes)->save();

            $this->events->write($instance, 'marked_defective', [
                'performed_by' => $context['performed_by'] ?? null,
                'from_status' => $fromStatus,
                'to_status' => $instance->status,
                'from_asset_id' => $fromAssetId,
                'from_storage_location_id' => $fromStorageLocationId,
                'to_storage_location_id' => $instance->storage_location_id,
                'held_by_user_id' => $heldByUserId,
                'note' => $context['note'] ?? null,
                'payload_json' => [
                    'from_condition_status' => $fromConditionStatus,
                    'to_condition_status' => ComponentInstance::CONDITION_STATUS_DAMAGED,
                ],
            ]);

            return $instance->fresh();
        });
    }

    public function markDestructionPending(
        ComponentInstance $instance,
        ?ComponentStorageLocation $location = null,
        array $context = [],
    ): ComponentInstance {
        $this->assertNotTerminal($instance);
        $this->assertStorageLocationCompany($instance, $location);

        return DB::transaction(function () use ($instance, $location, $context): ComponentInstance {
            $detachedChild = $this->prepareDetachedChildSnapshot($instance);
            $attachedChildren = $this->attachedChildrenForParentMove($instance);
            $fromStatus = $instance->status;
            $fromAssetId = $instance->current_asset_id;
            $fromStorageLocationId = $instance->storage_location_id;
            $heldByUserId = $instance->held_by_user_id;

            $instance->forceFill(array_merge([
                'status' => ComponentInstance::STATUS_DESTRUCTION_PENDING,
                'lifecycle_status' => ComponentInstance::LIFECYCLE_DESTRUCTION_PENDING,
                'current_asset_id' => null,
                'parent_component_instance_id' => null,
                'root_asset_id' => null,
                'storage_location_id' => $location?->id,
                'held_by_user_id' => null,
                'transfer_started_at' => null,
                'updated_by' => $this->resolveActorId($context['performed_by'] ?? null),
            ], $this->detachedChildInstanceAttributes($detachedChild)))->save();

            $event = $this->events->write($instance, 'marked_destruction_pending', [
                'performed_by' => $context['performed_by'] ?? null,
                'from_status' => $fromStatus,
                'to_status' => ComponentInstance::STATUS_DESTRUCTION_PENDING,
                'from_asset_id' => $fromAssetId,
                'from_storage_location_id' => $fromStorageLocationId,
                'to_storage_location_id' => $location?->id,
                'held_by_user_id' => $heldByUserId,
                'note' => $context['note'] ?? null,
                'payload_json' => $this->mergeAttachedChildMovePayload(
                    $attachedChildren,
                    $this->mergeDetachedChildPayload($detachedChild, $context['payload_json'] ?? null)
                ),
            ]);

            $this->completeDetachedChildEventSnapshot($instance, $detachedChild, $event);
            $this->markAttachedChildrenDestructionPendingWithParent($instance, $attachedChildren, $location, $context, $event);

            return $instance->fresh();
        });
    }

    public function markDestroyed(
        ComponentInstance $instance,
        array $context = [],
    ): ComponentInstance {
        $this->assertCanMarkDestroyed($instance, $context);

        return DB::transaction(function () use ($instance, $context): ComponentInstance {
            $attachedChildren = $this->childrenForParentDestruction($instance);
            $fromStatus = $instance->status;
            $fromAssetId = $instance->current_asset_id;
            $fromStorageLocationId = $instance->storage_location_id;

            $instance->forceFill([
                'status' => ComponentInstance::STATUS_DESTROYED_RECYCLED,
                'lifecycle_status' => ComponentInstance::LIFECYCLE_DESTROYED,
                'current_asset_id' => null,
                'parent_component_instance_id' => null,
                'root_asset_id' => null,
                'held_by_user_id' => null,
                'transfer_started_at' => null,
                'destroyed_at' => $context['destroyed_at'] ?? now(),
                'updated_by' => $this->resolveActorId($context['performed_by'] ?? null),
            ])->save();

            $event = $this->events->write($instance, 'destroyed_recycled', [
                'performed_by' => $context['performed_by'] ?? null,
                'from_status' => $fromStatus,
                'to_status' => ComponentInstance::STATUS_DESTROYED_RECYCLED,
                'from_asset_id' => $fromAssetId,
                'from_storage_location_id' => $fromStorageLocationId,
                'to_storage_location_id' => $instance->storage_location_id,
                'note' => $context['note'] ?? null,
                'payload_json' => $this->mergeAttachedChildMovePayload(
                    $attachedChildren,
                    is_array($context['payload_json'] ?? null) ? $context['payload_json'] : null
                ),
            ]);

            $this->markAttachedChildrenDestroyedWithParent($instance, $attachedChildren, $context, $event);

            return $instance->fresh();
        });
    }

    protected function assertNotTerminal(ComponentInstance $instance): void
    {
        if ($instance->isTerminalLifecycle() || in_array($instance->status, [
            ComponentInstance::STATUS_DESTROYED_RECYCLED,
            ComponentInstance::STATUS_SOLD_RETURNED,
        ], true)) {
            throw new InvalidArgumentException('Component is already in a terminal state.');
        }
    }

    protected function assertCanInstall(ComponentInstance $instance): void
    {
        $lifecycleStatus = $instance->effectiveLifecycleStatus();

        if (in_array($lifecycleStatus, [
            ComponentInstance::LIFECYCLE_DESTROYED,
            ComponentInstance::LIFECYCLE_DESTRUCTION_PENDING,
        ], true) || in_array($instance->status, [
            ComponentInstance::STATUS_DESTROYED_RECYCLED,
            ComponentInstance::STATUS_DESTRUCTION_PENDING,
        ], true)) {
            throw new InvalidArgumentException('Destroyed or destruction-pending components cannot be installed.');
        }
    }

    protected function assertComponentDefinitionCanBeInstalledOnAsset(ComponentInstance $instance): void
    {
        $instance->loadMissing('componentDefinition');

        if ($instance->componentDefinition && !$instance->componentDefinition->canBeInstalledOnAsset()) {
            throw new InvalidArgumentException('This component definition is restricted to subcomponent placement and cannot be installed directly on an asset.');
        }
    }

    protected function assertCanMarkDestroyed(ComponentInstance $instance, array $context): void
    {
        if ($instance->effectiveLifecycleStatus() !== ComponentInstance::LIFECYCLE_DESTRUCTION_PENDING
            && $instance->status !== ComponentInstance::STATUS_DESTRUCTION_PENDING
        ) {
            throw new InvalidArgumentException('Component must be marked destruction pending before it can be destroyed.');
        }

        $note = trim((string) ($context['note'] ?? ''));
        $evidence = $context['payload_json'] ?? null;

        if ($note === '' && (empty($evidence) || !is_array($evidence))) {
            throw new InvalidArgumentException('A destruction note or verification evidence is required before marking a component destroyed.');
        }
    }

    private function assertCanReparentWithinAsset(
        ComponentInstance $instance,
        ?ComponentInstance $parent
    ): void {
        $this->assertNotTerminal($instance);
        $instance->loadMissing('componentDefinition');

        if (!$instance->current_asset_id
            || $instance->effectiveLifecycleStatus() !== ComponentInstance::LIFECYCLE_ATTACHED
        ) {
            throw new InvalidArgumentException('Only attached components can be moved within an asset.');
        }

        if (!$parent) {
            if ($instance->componentDefinition && !$instance->componentDefinition->canBeInstalledOnAsset()) {
                throw new InvalidArgumentException(
                    'This component definition is restricted to subcomponent placement and cannot be installed directly on an asset.'
                );
            }

            return;
        }

        $this->assertNotTerminal($parent);

        if ((int) $parent->id === (int) $instance->id) {
            throw new InvalidArgumentException('A component cannot be attached to itself.');
        }

        if ((int) $parent->current_asset_id !== (int) $instance->current_asset_id) {
            throw new InvalidArgumentException('The parent component must be attached to the same asset.');
        }

        if ($parent->parent_component_instance_id) {
            throw new InvalidArgumentException('Component hierarchy is limited to one subcomponent level.');
        }

        if ($parent->effectiveLifecycleStatus() !== ComponentInstance::LIFECYCLE_ATTACHED) {
            throw new InvalidArgumentException('The parent component must be attached to the asset.');
        }

        if (ComponentInstance::withoutGlobalScope(CompanyableScope::class)
            ->where('parent_component_instance_id', $instance->id)
            ->exists()
        ) {
            throw new InvalidArgumentException(
                'A component with attached child components cannot also become a subcomponent.'
            );
        }

        if ($instance->componentDefinition && !$instance->componentDefinition->canBeUsedAsSubcomponent()) {
            throw new InvalidArgumentException(
                'This component definition is restricted to direct asset placement and cannot be used as a subcomponent.'
            );
        }
    }

    private function lockLiveParentForChildCreation(array $attributes): void
    {
        $parentId = (int) ($attributes['parent_component_instance_id'] ?? 0);
        if ($parentId <= 0) {
            return;
        }

        $parent = ComponentInstance::withoutGlobalScope(CompanyableScope::class)
            ->whereKey($parentId)
            ->lockForUpdate()
            ->first();

        if (!$parent) {
            throw new InvalidArgumentException('Parent component could not be found.');
        }
    }

    private function reconcileExpectedSubcomponentReparent(
        ComponentInstance $instance,
        ?ComponentInstance $fromParent,
        ?ComponentInstance $toParent
    ): array {
        $template = $this->expectedSubcomponentTemplateFor($instance);
        if (!$template) {
            return [];
        }

        $this->assertExpectedSubcomponentTemplateMatchesChild($instance, $template);
        if ($fromParent) {
            $this->assertExpectedSubcomponentTemplateMatchesParent($fromParent, $template);
        }
        if ($toParent) {
            $this->assertExpectedSubcomponentTemplateMatchesParent($toParent, $template);
        }

        $changes = [
            'component_definition_subcomponent_template_id' => $template->id,
        ];

        if ($fromParent) {
            $changes['from_parent'] = $this->releaseExpectedSubcomponentSlot(
                $fromParent,
                $template,
                $toParent === null
            );
        }

        if ($toParent) {
            $changes['to_parent'] = $this->claimExpectedSubcomponentSlot($toParent, $template);
        }

        return $changes;
    }

    private function reconcileExpectedSubcomponentDeletion(ComponentInstance $instance): array
    {
        if (!$instance->parent_component_instance_id || !$instance->is_materialized_expected) {
            return [];
        }

        $template = $this->expectedSubcomponentTemplateFor($instance);
        if (!$template) {
            return [];
        }

        $parent = ComponentInstance::withoutGlobalScope(CompanyableScope::class)
            ->withTrashed()
            ->whereKey((int) $instance->parent_component_instance_id)
            ->lockForUpdate()
            ->first();

        if (!$parent) {
            throw new InvalidArgumentException(
                'The expected subcomponent parent could not be found; deletion was stopped to preserve expected counts.'
            );
        }

        $this->assertExpectedSubcomponentTemplateMatchesChild($instance, $template);
        $this->assertExpectedSubcomponentTemplateMatchesParent($parent, $template);

        return [
            'component_definition_subcomponent_template_id' => $template->id,
            'from_parent' => $this->releaseExpectedSubcomponentSlot($parent, $template, true),
        ];
    }

    private function expectedSubcomponentTemplateFor(
        ComponentInstance $instance
    ): ?ComponentDefinitionSubcomponentTemplate {
        if (!$instance->is_materialized_expected) {
            return null;
        }

        $templateId = (int) data_get(
            $instance->metadata_json,
            'component_definition_subcomponent_template_id',
            0
        );

        if ($templateId <= 0) {
            throw new InvalidArgumentException(
                'Expected subcomponent template metadata is missing; the hierarchy change was stopped.'
            );
        }

        $template = ComponentDefinitionSubcomponentTemplate::query()->find($templateId);
        if (!$template) {
            throw new InvalidArgumentException(
                'Expected subcomponent template could not be found; the hierarchy change was stopped.'
            );
        }

        return $template;
    }

    private function assertExpectedSubcomponentTemplateMatchesChild(
        ComponentInstance $instance,
        ComponentDefinitionSubcomponentTemplate $template
    ): void {
        if ($instance->component_definition_id
            && (int) $instance->component_definition_id !== (int) $template->child_component_definition_id
        ) {
            throw new InvalidArgumentException(
                'Expected subcomponent definition does not match its materialization template.'
            );
        }
    }

    private function assertExpectedSubcomponentTemplateMatchesParent(
        ComponentInstance $parent,
        ComponentDefinitionSubcomponentTemplate $template
    ): void {
        if ((int) $parent->component_definition_id !== (int) $template->parent_component_definition_id) {
            throw new InvalidArgumentException(
                'Expected subcomponents can only be moved to a parent that owns the same expected template.'
            );
        }
    }

    private function releaseExpectedSubcomponentSlot(
        ComponentInstance $parent,
        ComponentDefinitionSubcomponentTemplate $template,
        bool $markRemoved
    ): array {
        $state = $this->expectedSubcomponentStateForUpdate($parent, $template);
        $expectedQty = max(1, (int) $template->expected_qty);
        $actualMaterializedQty = $this->liveExpectedSubcomponentCount($parent, $template);
        $removedQty = max(0, (int) $state->removed_qty);

        if ($actualMaterializedQty < 1) {
            throw new InvalidArgumentException(
                'Expected subcomponent materialization state is inconsistent; the hierarchy change was stopped.'
            );
        }

        if ($actualMaterializedQty + $removedQty > $expectedQty) {
            throw new InvalidArgumentException(
                'Expected subcomponent counts exceed the configured quantity; the hierarchy change was stopped.'
            );
        }

        $newMaterializedQty = $actualMaterializedQty - 1;
        $newRemovedQty = $removedQty + ($markRemoved ? 1 : 0);

        if ($newMaterializedQty + $newRemovedQty > $expectedQty) {
            throw new InvalidArgumentException(
                'Expected subcomponent counts would exceed the configured quantity; the hierarchy change was stopped.'
            );
        }

        $before = [
            'component_instance_id' => $parent->id,
            'removed_qty' => (int) $state->removed_qty,
            'materialized_qty' => (int) $state->materialized_qty,
        ];

        $state->forceFill([
            'removed_qty' => $newRemovedQty,
            'materialized_qty' => $newMaterializedQty,
        ])->save();

        return [
            'before' => $before,
            'after' => [
                'component_instance_id' => $parent->id,
                'removed_qty' => $newRemovedQty,
                'materialized_qty' => $newMaterializedQty,
            ],
        ];
    }

    private function claimExpectedSubcomponentSlot(
        ComponentInstance $parent,
        ComponentDefinitionSubcomponentTemplate $template
    ): array {
        $state = $this->expectedSubcomponentStateForUpdate($parent, $template);
        $expectedQty = max(1, (int) $template->expected_qty);
        $actualMaterializedQty = $this->liveExpectedSubcomponentCount($parent, $template);
        $removedQty = max(0, (int) $state->removed_qty);

        if ($actualMaterializedQty + $removedQty > $expectedQty) {
            throw new InvalidArgumentException(
                'Expected subcomponent counts exceed the configured quantity; the hierarchy change was stopped.'
            );
        }

        if ($actualMaterializedQty >= $expectedQty) {
            throw new InvalidArgumentException(
                'The destination parent has no remaining expected slot for this subcomponent.'
            );
        }

        // A tracked expected child restores a previously removed slot before
        // consuming an untouched virtual baseline slot.
        if ($removedQty > 0) {
            $removedQty--;
        }

        $before = [
            'component_instance_id' => $parent->id,
            'removed_qty' => (int) $state->removed_qty,
            'materialized_qty' => (int) $state->materialized_qty,
        ];
        $newMaterializedQty = $actualMaterializedQty + 1;

        $state->forceFill([
            'removed_qty' => $removedQty,
            'materialized_qty' => $newMaterializedQty,
        ])->save();

        return [
            'before' => $before,
            'after' => [
                'component_instance_id' => $parent->id,
                'removed_qty' => $removedQty,
                'materialized_qty' => $newMaterializedQty,
            ],
        ];
    }

    private function expectedSubcomponentStateForUpdate(
        ComponentInstance $parent,
        ComponentDefinitionSubcomponentTemplate $template
    ): ComponentExpectedSubcomponentState {
        ComponentExpectedSubcomponentState::query()->firstOrCreate(
            [
                'component_instance_id' => $parent->id,
                'component_definition_subcomponent_template_id' => $template->id,
            ],
            [
                'removed_qty' => 0,
                'materialized_qty' => 0,
            ]
        );

        return ComponentExpectedSubcomponentState::query()
            ->where('component_instance_id', $parent->id)
            ->where('component_definition_subcomponent_template_id', $template->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function liveExpectedSubcomponentCount(
        ComponentInstance $parent,
        ComponentDefinitionSubcomponentTemplate $template
    ): int {
        return ComponentInstance::withoutGlobalScope(CompanyableScope::class)
            ->where('parent_component_instance_id', $parent->id)
            ->where('is_materialized_expected', true)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'metadata_json'])
            ->filter(
                fn (ComponentInstance $child): bool => (int) data_get(
                    $child->metadata_json,
                    'component_definition_subcomponent_template_id',
                    0
                ) === (int) $template->id
            )
            ->count();
    }

    private function reparentedMetadata(
        ComponentInstance $instance,
        ?int $fromParentId,
        ?int $targetParentId
    ): ?array {
        $metadata = $instance->metadata_json;
        if (!is_array($metadata)) {
            return null;
        }

        if ($fromParentId && !array_key_exists('origin_parent_component_instance_id', $metadata)) {
            $metadata['origin_parent_component_instance_id'] = $fromParentId;
        }

        if (array_key_exists('parent_component_instance_id', $metadata)
            || $instance->is_materialized_expected
            || !empty($metadata['manual_child_component'])
        ) {
            $metadata['parent_component_instance_id'] = $targetParentId;
        }

        return $metadata;
    }

    private function writeDeleteActionLog(
        ComponentInstance $instance,
        ?int $actorId,
        ?string $note
    ): void {
        $timestamp = now();
        $logAction = new Actionlog();
        $logAction->item_type = ComponentInstance::class;
        $logAction->item_id = $instance->id;
        $logAction->created_at = $timestamp;
        $logAction->action_date = $timestamp;
        $logAction->created_by = $actorId;
        $logAction->note = $note;

        if (!$logAction->logaction('delete')) {
            throw new \RuntimeException('Component deletion audit log could not be persisted.');
        }
    }

    private function prepareDetachedChildSnapshot(ComponentInstance $instance): array
    {
        if (!$instance->parent_component_instance_id) {
            return [];
        }

        $parentId = (int) $instance->parent_component_instance_id;
        $templateId = (int) data_get($instance->metadata_json, 'component_definition_subcomponent_template_id', 0);

        if ($instance->is_materialized_expected && $templateId > 0) {
            $this->markExpectedSubcomponentRemoved($parentId, $templateId);
        }

        return [
            'parent_component_instance_id' => $parentId,
            'component_definition_subcomponent_template_id' => $templateId ?: null,
            'attached_through_at' => now(),
        ];
    }

    private function markExpectedSubcomponentRemoved(int $parentComponentInstanceId, int $templateId): void
    {
        $state = ComponentExpectedSubcomponentState::query()->firstOrCreate(
            [
                'component_instance_id' => $parentComponentInstanceId,
                'component_definition_subcomponent_template_id' => $templateId,
            ],
            [
                'removed_qty' => 0,
                'materialized_qty' => 0,
            ]
        );

        $state->forceFill([
            'materialized_qty' => max(0, (int) $state->materialized_qty - 1),
            'removed_qty' => max(0, (int) $state->removed_qty) + 1,
        ])->save();
    }

    private function detachedChildInstanceAttributes(array $detachedChild): array
    {
        if ($detachedChild === []) {
            return [];
        }

        return [
            'ancestry_parent_component_instance_id' => $detachedChild['parent_component_instance_id'],
            'ancestry_attached_through_at' => $detachedChild['attached_through_at'],
            'ancestry_attached_through_event_id' => null,
        ];
    }

    private function mergeDetachedChildPayload(array $detachedChild, mixed $payload): ?array
    {
        $payload = is_array($payload) ? $payload : [];

        if ($detachedChild === []) {
            return $payload === [] ? null : $payload;
        }

        if ($detachedChild !== []) {
            $payload = array_merge($payload, [
                'detached_parent_component_instance_id' => $detachedChild['parent_component_instance_id'],
                'component_definition_subcomponent_template_id' => $detachedChild['component_definition_subcomponent_template_id'],
            ]);
        }

        $payload = array_filter($payload, fn ($value) => $value !== null && $value !== '');

        return $payload === [] ? null : $payload;
    }

    private function serialUpdateAttributes(array $context): array
    {
        if (!array_key_exists('serial', $context)) {
            return [];
        }

        return [
            'serial' => $this->normalizeComponentSerial($context['serial']),
        ];
    }

    private function normalizeComponentSerial(mixed $serial): ?string
    {
        $value = trim((string) ($serial ?? ''));

        return $value !== '' ? $value : null;
    }

    private function mergeSerialChangePayload(mixed $payload, ?string $fromSerial, ?string $toSerial): ?array
    {
        $payload = is_array($payload) ? $payload : [];

        if ($fromSerial !== $toSerial) {
            $payload['serial_changed'] = true;
            $payload['previous_serial'] = $fromSerial;
            $payload['new_serial'] = $toSerial;
        }

        return $payload === [] ? null : $payload;
    }

    private function completeDetachedChildEventSnapshot(ComponentInstance $instance, array $detachedChild, ComponentEvent $event): void
    {
        if ($detachedChild === []) {
            return;
        }

        $instance->forceFill([
            'ancestry_attached_through_event_id' => $event->id,
        ])->save();
    }

    private function attachedChildrenForParentMove(ComponentInstance $instance): Collection
    {
        if ($instance->parent_component_instance_id) {
            return collect();
        }

        return ComponentInstance::query()
            ->where('parent_component_instance_id', $instance->id)
            ->where(function ($query): void {
                $query
                    ->where('lifecycle_status', ComponentInstance::LIFECYCLE_ATTACHED)
                    ->orWhere('status', ComponentInstance::STATUS_INSTALLED);
            })
            ->orderBy('id')
            ->get();
    }

    private function childrenForParentDestruction(ComponentInstance $instance): Collection
    {
        if ($instance->parent_component_instance_id) {
            return collect();
        }

        return ComponentInstance::query()
            ->where('parent_component_instance_id', $instance->id)
            ->where(function ($query): void {
                $query
                    ->whereIn('lifecycle_status', [
                        ComponentInstance::LIFECYCLE_ATTACHED,
                        ComponentInstance::LIFECYCLE_DESTRUCTION_PENDING,
                    ])
                    ->orWhereIn('status', [
                        ComponentInstance::STATUS_INSTALLED,
                        ComponentInstance::STATUS_DESTRUCTION_PENDING,
                    ]);
            })
            ->orderBy('id')
            ->get();
    }

    private function mergeAttachedChildMovePayload(Collection $attachedChildren, ?array $payload): ?array
    {
        $payload = $payload ?? [];

        if ($attachedChildren->isNotEmpty()) {
            $payload['moved_child_component_ids'] = $attachedChildren->pluck('id')->values()->all();
            $payload['moved_child_count'] = $attachedChildren->count();
        }

        return $payload === [] ? null : $payload;
    }

    private function moveAttachedChildrenWithParent(
        ComponentInstance $parent,
        Collection $attachedChildren,
        Asset $asset,
        array $context,
        ComponentEvent $parentEvent,
    ): void {
        if ($attachedChildren->isEmpty()) {
            return;
        }

        foreach ($attachedChildren as $child) {
            $fromStatus = $child->status;
            $fromAssetId = $child->current_asset_id;
            $fromStorageLocationId = $child->storage_location_id;
            $heldByUserId = $child->held_by_user_id;

            $child->forceFill([
                'status' => ComponentInstance::STATUS_INSTALLED,
                'lifecycle_status' => ComponentInstance::LIFECYCLE_ATTACHED,
                'company_id' => $parent->company_id ?: $asset->company_id ?: $child->company_id,
                'current_asset_id' => $asset->id,
                'root_asset_id' => $asset->id,
                'storage_location_id' => null,
                'held_by_user_id' => null,
                'transfer_started_at' => null,
                'updated_by' => $this->resolveActorId($context['performed_by'] ?? null),
            ])->save();

            $this->events->write($child, 'moved_with_parent', [
                'performed_by' => $context['performed_by'] ?? null,
                'from_status' => $fromStatus,
                'to_status' => $child->status,
                'from_asset_id' => $fromAssetId,
                'to_asset_id' => $asset->id,
                'from_storage_location_id' => $fromStorageLocationId,
                'held_by_user_id' => $heldByUserId,
                'related_work_order_id' => $context['related_work_order_id'] ?? null,
                'related_work_order_task_id' => $context['related_work_order_task_id'] ?? null,
                'note' => $context['note'] ?? null,
                'payload_json' => [
                    'parent_component_instance_id' => $parent->id,
                    'parent_component_event_id' => $parentEvent->id,
                ],
            ]);
        }
    }

    private function moveAttachedChildrenOffAssetWithParent(
        ComponentInstance $parent,
        Collection $attachedChildren,
        array $context,
        ComponentEvent $parentEvent,
    ): void {
        if ($attachedChildren->isEmpty()) {
            return;
        }

        foreach ($attachedChildren as $child) {
            $fromStatus = $child->status;
            $fromAssetId = $child->current_asset_id;
            $fromStorageLocationId = $child->storage_location_id;
            $heldByUserId = $child->held_by_user_id;

            $child->forceFill([
                'status' => ComponentInstance::STATUS_INSTALLED,
                'lifecycle_status' => ComponentInstance::LIFECYCLE_ATTACHED,
                'current_asset_id' => null,
                'root_asset_id' => null,
                'storage_location_id' => null,
                'held_by_user_id' => null,
                'transfer_started_at' => null,
                'updated_by' => $this->resolveActorId($context['performed_by'] ?? null),
            ])->save();

            $this->events->write($child, 'moved_with_parent', [
                'performed_by' => $context['performed_by'] ?? null,
                'from_status' => $fromStatus,
                'to_status' => $child->status,
                'from_asset_id' => $fromAssetId,
                'from_storage_location_id' => $fromStorageLocationId,
                'held_by_user_id' => $heldByUserId,
                'related_work_order_id' => $context['related_work_order_id'] ?? null,
                'related_work_order_task_id' => $context['related_work_order_task_id'] ?? null,
                'note' => $context['note'] ?? null,
                'payload_json' => [
                    'parent_component_instance_id' => $parent->id,
                    'parent_component_event_id' => $parentEvent->id,
                ],
            ]);
        }
    }

    private function markAttachedChildrenDestructionPendingWithParent(
        ComponentInstance $parent,
        Collection $attachedChildren,
        ?ComponentStorageLocation $location,
        array $context,
        ComponentEvent $parentEvent,
    ): void {
        if ($attachedChildren->isEmpty()) {
            return;
        }

        foreach ($attachedChildren as $child) {
            $fromStatus = $child->status;
            $fromAssetId = $child->current_asset_id;
            $fromStorageLocationId = $child->storage_location_id;
            $heldByUserId = $child->held_by_user_id;

            $child->forceFill([
                'status' => ComponentInstance::STATUS_DESTRUCTION_PENDING,
                'lifecycle_status' => ComponentInstance::LIFECYCLE_DESTRUCTION_PENDING,
                'current_asset_id' => null,
                'root_asset_id' => null,
                'storage_location_id' => $location?->id,
                'held_by_user_id' => null,
                'transfer_started_at' => null,
                'updated_by' => $this->resolveActorId($context['performed_by'] ?? null),
            ])->save();

            $this->events->write($child, 'marked_destruction_pending', [
                'performed_by' => $context['performed_by'] ?? null,
                'from_status' => $fromStatus,
                'to_status' => ComponentInstance::STATUS_DESTRUCTION_PENDING,
                'from_asset_id' => $fromAssetId,
                'from_storage_location_id' => $fromStorageLocationId,
                'to_storage_location_id' => $location?->id,
                'held_by_user_id' => $heldByUserId,
                'related_work_order_id' => $context['related_work_order_id'] ?? null,
                'related_work_order_task_id' => $context['related_work_order_task_id'] ?? null,
                'note' => $context['note'] ?? null,
                'payload_json' => [
                    'parent_component_instance_id' => $parent->id,
                    'parent_component_event_id' => $parentEvent->id,
                ],
            ]);
        }
    }

    private function markAttachedChildrenDestroyedWithParent(
        ComponentInstance $parent,
        Collection $attachedChildren,
        array $context,
        ComponentEvent $parentEvent,
    ): void {
        if ($attachedChildren->isEmpty()) {
            return;
        }

        foreach ($attachedChildren as $child) {
            $fromStatus = $child->status;
            $fromAssetId = $child->current_asset_id;
            $fromStorageLocationId = $child->storage_location_id;

            $child->forceFill([
                'status' => ComponentInstance::STATUS_DESTROYED_RECYCLED,
                'lifecycle_status' => ComponentInstance::LIFECYCLE_DESTROYED,
                'current_asset_id' => null,
                'root_asset_id' => null,
                'held_by_user_id' => null,
                'transfer_started_at' => null,
                'destroyed_at' => $context['destroyed_at'] ?? now(),
                'updated_by' => $this->resolveActorId($context['performed_by'] ?? null),
            ])->save();

            $this->events->write($child, 'destroyed_recycled', [
                'performed_by' => $context['performed_by'] ?? null,
                'from_status' => $fromStatus,
                'to_status' => ComponentInstance::STATUS_DESTROYED_RECYCLED,
                'from_asset_id' => $fromAssetId,
                'from_storage_location_id' => $fromStorageLocationId,
                'to_storage_location_id' => $child->storage_location_id,
                'note' => $context['note'] ?? null,
                'payload_json' => [
                    'parent_component_instance_id' => $parent->id,
                    'parent_component_event_id' => $parentEvent->id,
                ],
            ]);
        }
    }

    protected function assertTrayHolderCanInstall(ComponentInstance $instance, User|int|null $performedBy = null): void
    {
        if ($instance->effectiveLifecycleStatus() !== ComponentInstance::LIFECYCLE_IN_TRAY || !$instance->held_by_user_id) {
            return;
        }

        $actorId = $this->resolveActorId($performedBy);

        if ($actorId !== $instance->held_by_user_id) {
            throw new InvalidArgumentException('Tray components can only be installed by the user who currently holds them.');
        }
    }

    public function assertConditionWarningConfirmed(ComponentInstance $instance, array $context = []): void
    {
        if (!$instance->requiresConditionWarningForAttachment()) {
            return;
        }

        if ($this->conditionWarningConfirmed($context)) {
            return;
        }

        throw ComponentConditionWarningException::forComponent($instance);
    }

    public function assertLifecycleWarningConfirmed(ComponentInstance $instance, array $context = []): void
    {
        if (!$instance->requiresLifecycleWarningForAttachment()) {
            return;
        }

        if ($this->lifecycleWarningConfirmed($context)) {
            return;
        }

        throw ComponentLifecycleWarningException::forComponent($instance);
    }

    public function assertConditionWarningConfirmedForCondition(
        string $conditionStatus,
        array $context = [],
        ?string $componentName = null,
    ): void {
        if (!in_array($conditionStatus, ComponentInstance::attachmentWarningConditionStatuses(), true)) {
            return;
        }

        if ($this->conditionWarningConfirmed($context)) {
            return;
        }

        throw ComponentConditionWarningException::forCondition($conditionStatus, $componentName);
    }

    protected function conditionWarningConfirmed(array $context): bool
    {
        return filter_var($context['condition_warning_confirmed'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    protected function lifecycleWarningConfirmed(array $context): bool
    {
        return filter_var($context['lifecycle_warning_confirmed'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    protected function resolveActorId(User|int|null $actor): ?int
    {
        if ($actor instanceof User) {
            return $actor->id;
        }

        return $actor ?? auth()->id();
    }

    protected function normalizeInstanceAttributes(array $attributes, ?int $actorId): array
    {
        $attributes['company_id'] = $this->resolveInstanceCompanyId($attributes, $actorId);
        $attributes = $this->normalizeLifecycleCompatibilityAttributes($attributes);
        $attributes = $this->normalizeHierarchyAttributes($attributes);

        return $attributes;
    }

    protected function activeDefinitionForInstanceAttributes(array $attributes): ?ComponentDefinition
    {
        $definitionId = $attributes['component_definition_id'] ?? null;

        if (!$definitionId) {
            return null;
        }

        $definition = ComponentDefinition::query()->find((int) $definitionId);

        if (!$definition) {
            throw new InvalidArgumentException('Component definition could not be found.');
        }

        if (!$definition->is_active) {
            throw new InvalidArgumentException('The selected component definition is not active.');
        }

        return $definition;
    }

    protected function assertPlacementAllowedForInstanceAttributes(
        array $attributes,
        ?ComponentDefinition $definition = null
    ): void
    {
        $definitionId = $attributes['component_definition_id'] ?? null;

        if (!$definitionId) {
            return;
        }

        $definition ??= ComponentDefinition::query()->find((int) $definitionId);

        if (!$definition) {
            throw new InvalidArgumentException('Component definition could not be found.');
        }

        if (!empty($attributes['parent_component_instance_id'])) {
            if (!$definition->canBeUsedAsSubcomponent()) {
                throw new InvalidArgumentException('This component definition is restricted to direct asset placement and cannot be used as a subcomponent.');
            }

            return;
        }

        $isAssetAttachment = !empty($attributes['current_asset_id'])
            || ($attributes['lifecycle_status'] ?? null) === ComponentInstance::LIFECYCLE_ATTACHED
            || ($attributes['status'] ?? null) === ComponentInstance::STATUS_INSTALLED;

        if ($isAssetAttachment && !$definition->canBeInstalledOnAsset()) {
            throw new InvalidArgumentException('This component definition is restricted to subcomponent placement and cannot be installed directly on an asset.');
        }
    }

    protected function assertComponentDefinitionChangeIsSafe(
        ComponentInstance $instance,
        ?int $definitionId
    ): void {
        $currentDefinitionId = $instance->component_definition_id
            ? (int) $instance->component_definition_id
            : null;

        if ($definitionId === $currentDefinitionId) {
            return;
        }

        $definition = $definitionId
            ? ComponentDefinition::query()->where('is_active', true)->find($definitionId)
            : null;

        if ($definitionId && !$definition) {
            throw new InvalidArgumentException('The selected component definition is not active.');
        }

        if ($instance->childComponents()->exists() || $instance->expectedSubcomponentStates()->exists()) {
            throw new InvalidArgumentException(
                'A component definition cannot be changed while the component has child or expected-subcomponent state.'
            );
        }

        $expectedTemplateId = (int) data_get(
            $instance->metadata_json,
            'component_definition_subcomponent_template_id',
            0
        );

        if ($expectedTemplateId > 0) {
            $template = ComponentDefinitionSubcomponentTemplate::query()->find($expectedTemplateId);

            if ($template && (int) $template->child_component_definition_id !== (int) $definitionId) {
                throw new InvalidArgumentException(
                    'An expected component must keep the definition declared by its expected-subcomponent template.'
                );
            }
        }

        $this->assertSerialMatchesDefinition($definition, $instance->serial);
    }

    protected function assertSerialMatchesDefinition(
        ?ComponentDefinition $definition,
        mixed $serial
    ): void {
        $serial = trim((string) ($serial ?? ''));

        if ($definition?->serial_tracking_mode === 'required' && $serial === '') {
            throw new InvalidArgumentException('A serial number is required for this component definition.');
        }

        if ($definition?->serial_tracking_mode === 'not_tracked' && $serial !== '') {
            throw new InvalidArgumentException('This component definition does not track serial numbers.');
        }
    }

    protected function normalizeLifecycleCompatibilityAttributes(array $attributes): array
    {
        $hasLifecycleStatus = array_key_exists('lifecycle_status', $attributes)
            && filled($attributes['lifecycle_status']);
        $hasStatus = array_key_exists('status', $attributes)
            && filled($attributes['status']);

        if ($hasLifecycleStatus && !$hasStatus) {
            $attributes['status'] = ComponentInstance::legacyStatusForLifecycleStatus($attributes['lifecycle_status']);
        }

        $hasConditionStatus = array_key_exists('condition_status', $attributes)
            && filled($attributes['condition_status']);
        $hasConditionCode = array_key_exists('condition_code', $attributes)
            && filled($attributes['condition_code']);

        if ($hasConditionStatus && !$hasConditionCode) {
            $attributes['condition_code'] = ComponentInstance::legacyConditionCodeForConditionStatus($attributes['condition_status']);
        }

        return $attributes;
    }

    protected function normalizeHierarchyAttributes(array $attributes): array
    {
        $parentId = $attributes['parent_component_instance_id'] ?? null;

        if ($parentId !== null && $parentId !== '') {
            $parent = ComponentInstance::query()->whereKey((int) $parentId)->first();

            if ($parent) {
                if (!array_key_exists('current_asset_id', $attributes) || $attributes['current_asset_id'] === null || $attributes['current_asset_id'] === '') {
                    $attributes['current_asset_id'] = $parent->current_asset_id;
                }

                if (!array_key_exists('root_asset_id', $attributes) || $attributes['root_asset_id'] === null || $attributes['root_asset_id'] === '') {
                    $attributes['root_asset_id'] = $parent->root_asset_id ?: $parent->current_asset_id;
                }
            }

            return $attributes;
        }

        if (!empty($attributes['current_asset_id']) && (
            !array_key_exists('root_asset_id', $attributes)
            || $attributes['root_asset_id'] === null
            || $attributes['root_asset_id'] === ''
        )) {
            $attributes['root_asset_id'] = $attributes['current_asset_id'];
        }

        return $attributes;
    }

    protected function resolveInstanceCompanyId(array $attributes, ?int $actorId): ?int
    {
        if (array_key_exists('company_id', $attributes) && $attributes['company_id'] !== null && $attributes['company_id'] !== '') {
            $companyId = (int) $attributes['company_id'];
            $this->assertActorCanUseCompanyScope($actorId, $companyId);

            return $companyId;
        }

        foreach (['current_asset_id', 'source_asset_id'] as $assetKey) {
            if (!empty($attributes[$assetKey])) {
                $companyId = Asset::withoutGlobalScopes()
                    ->whereKey($attributes[$assetKey])
                    ->value('company_id');

                if ($companyId) {
                    $this->assertActorCanUseCompanyScope($actorId, (int) $companyId);

                    return (int) $companyId;
                }
            }
        }

        if ($actorId) {
            $companyId = User::withoutGlobalScopes()->whereKey($actorId)->value('company_id');

            if ($companyId) {
                return (int) $companyId;
            }
        }

        if ($this->requiresExplicitCompanyScope()) {
            throw new InvalidArgumentException('A company scope is required for tracked components when full multiple company support is enabled.');
        }

        return null;
    }

    protected function assertActorCanUseCompanyScope(?int $actorId, int $companyId): void
    {
        if (!$this->requiresExplicitCompanyScope() || !$actorId) {
            return;
        }

        $actor = User::withoutGlobalScopes()->find($actorId);

        if (!$actor || (!$actor->isSuperUser() && (int) $actor->company_id !== $companyId)) {
            throw new InvalidArgumentException('The component company is outside the acting user\'s access scope.');
        }
    }

    protected function assertStorageLocationCompany(
        ComponentInstance $instance,
        ?ComponentStorageLocation $location
    ): void {
        if (!$location || !$instance->company_id || !$location->site_location_id) {
            return;
        }

        $storageCompanyId = Location::withoutGlobalScopes()
            ->whereKey((int) $location->site_location_id)
            ->value('company_id');

        if ($storageCompanyId && (int) $storageCompanyId !== (int) $instance->company_id) {
            throw new InvalidArgumentException('Storage location belongs to a different company.');
        }
    }

    protected function assertCompanyConsistencyForInstanceAttributes(array $attributes): void
    {
        $companyId = isset($attributes['company_id']) && $attributes['company_id'] !== ''
            ? (int) $attributes['company_id']
            : null;

        if (!$companyId) {
            return;
        }

        foreach (['source_asset_id', 'current_asset_id', 'root_asset_id'] as $assetKey) {
            if (empty($attributes[$assetKey])) {
                continue;
            }

            $assetCompanyId = Asset::withoutGlobalScopes()
                ->whereKey((int) $attributes[$assetKey])
                ->value('company_id');

            if ($assetCompanyId && (int) $assetCompanyId !== $companyId) {
                throw new InvalidArgumentException('Component placement references an asset in a different company.');
            }
        }

        if (!empty($attributes['parent_component_instance_id'])) {
            $parentCompanyId = ComponentInstance::withoutGlobalScopes()
                ->whereKey((int) $attributes['parent_component_instance_id'])
                ->value('company_id');

            if ($parentCompanyId && (int) $parentCompanyId !== $companyId) {
                throw new InvalidArgumentException('Component placement references a parent component in a different company.');
            }
        }

        if (!empty($attributes['held_by_user_id'])) {
            $holderCompanyId = User::withoutGlobalScopes()
                ->whereKey((int) $attributes['held_by_user_id'])
                ->value('company_id');

            if ($holderCompanyId && (int) $holderCompanyId !== $companyId) {
                throw new InvalidArgumentException('Component placement references a tray holder in a different company.');
            }
        }

        if (!empty($attributes['storage_location_id'])) {
            $siteLocationId = ComponentStorageLocation::query()
                ->whereKey((int) $attributes['storage_location_id'])
                ->value('site_location_id');
            $storageCompanyId = $siteLocationId
                ? Location::withoutGlobalScopes()->whereKey((int) $siteLocationId)->value('company_id')
                : null;

            if ($storageCompanyId && (int) $storageCompanyId !== $companyId) {
                throw new InvalidArgumentException('Component placement references a storage location in a different company.');
            }
        }
    }

    protected function ensureInstanceCompanyId(ComponentInstance $instance): void
    {
        if (!$instance->company_id && $this->requiresExplicitCompanyScope()) {
            throw new InvalidArgumentException('A company scope is required for tracked components when full multiple company support is enabled.');
        }
    }

    protected function requiresExplicitCompanyScope(): bool
    {
        return (int) (Setting::getSettings()?->full_multiple_companies_support ?? 0) === 1;
    }
}
