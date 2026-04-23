<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderTask;
use Illuminate\Support\Facades\DB;
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
            'storage_location_id' => null,
            'held_by_user_id' => $holderId,
            'transfer_started_at' => $attributes['transfer_started_at'] ?? now(),
            'status' => ComponentInstance::STATUS_IN_TRANSFER,
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
            $holderId = $holder instanceof User ? $holder->id : $holder;
            $fromAssetId = $instance->current_asset_id;
            $fromStatus = $instance->status;
            $fromStorageLocationId = $instance->storage_location_id;

            $instance->forceFill([
                'status' => ComponentInstance::STATUS_IN_TRANSFER,
                'source_asset_id' => $instance->source_asset_id ?? $fromAssetId,
                'current_asset_id' => null,
                'storage_location_id' => null,
                'held_by_user_id' => $holderId,
                'transfer_started_at' => $context['transfer_started_at'] ?? now(),
                'updated_by' => $holderId,
            ])->save();

            $this->events->write($instance, 'removed_to_tray', [
                'performed_by' => $holder,
                'from_status' => $fromStatus,
                'to_status' => ComponentInstance::STATUS_IN_TRANSFER,
                'from_asset_id' => $fromAssetId,
                'from_storage_location_id' => $fromStorageLocationId,
                'held_by_user_id' => $holderId,
                'related_work_order_id' => $context['related_work_order_id'] ?? null,
                'related_work_order_task_id' => $context['related_work_order_task_id'] ?? null,
                'note' => $context['note'] ?? null,
                'payload_json' => $context['payload_json'] ?? null,
            ]);

            return $instance->fresh();
        });
    }

    public function installIntoAsset(
        ComponentInstance $instance,
        Asset $asset,
        array $context = [],
    ): ComponentInstance {
        $this->assertNotTerminal($instance);
        $this->assertTrayHolderCanInstall($instance, $context['performed_by'] ?? null);

        return DB::transaction(function () use ($instance, $asset, $context): ComponentInstance {
            $fromStatus = $instance->status;
            $fromAssetId = $instance->current_asset_id;
            $fromStorageLocationId = $instance->storage_location_id;
            $heldByUserId = $instance->held_by_user_id;

            $instance->forceFill([
                'status' => ComponentInstance::STATUS_INSTALLED,
                'company_id' => $context['company_id'] ?? $asset->company_id ?? $instance->company_id,
                'current_asset_id' => $asset->id,
                'storage_location_id' => null,
                'held_by_user_id' => null,
                'transfer_started_at' => null,
                'installed_as' => $context['installed_as'] ?? $instance->installed_as,
                'last_verified_at' => $context['last_verified_at'] ?? $instance->last_verified_at,
                'updated_by' => $this->resolveActorId($context['performed_by'] ?? null),
            ])->save();

            $this->ensureInstanceCompanyId($instance);

            $this->events->write($instance, 'installed', [
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
                'payload_json' => [
                    'installed_as' => $instance->installed_as,
                ],
            ]);

            return $instance->fresh();
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

        $updated = DB::transaction(function () use ($instance, $location, $context): ComponentInstance {
            $fromStatus = $instance->status;
            $fromAssetId = $instance->current_asset_id;
            $fromStorageLocationId = $instance->storage_location_id;
            $heldByUserId = $instance->held_by_user_id;

            $instance->forceFill([
                'status' => ComponentInstance::STATUS_IN_STOCK,
                'current_asset_id' => null,
                'storage_location_id' => $location?->id,
                'held_by_user_id' => null,
                'transfer_started_at' => null,
                'updated_by' => $this->resolveActorId($context['performed_by'] ?? null),
            ])->save();

            $this->events->write($instance, 'moved_to_stock', [
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
            ]);

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

        if ($instance->status === ComponentInstance::STATUS_INSTALLED) {
            throw new InvalidArgumentException('Installed components do not have a storage location.');
        }

        if ($instance->status === ComponentInstance::STATUS_IN_TRANSFER) {
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
            $fromStatus = $instance->status;
            $fromAssetId = $instance->current_asset_id;

            $instance->forceFill([
                'status' => ComponentInstance::STATUS_NEEDS_VERIFICATION,
                'current_asset_id' => null,
                'storage_location_id' => $location?->id,
                'held_by_user_id' => null,
                'transfer_started_at' => null,
                'needs_verification_at' => $context['needs_verification_at'] ?? now(),
                'updated_by' => $this->resolveActorId($context['performed_by'] ?? null),
            ])->save();

            $this->events->write($instance, 'flagged_needs_verification', [
                'performed_by' => $context['performed_by'] ?? null,
                'from_status' => $fromStatus,
                'to_status' => ComponentInstance::STATUS_NEEDS_VERIFICATION,
                'from_asset_id' => $fromAssetId,
                'to_storage_location_id' => $location?->id,
                'related_work_order_id' => $context['related_work_order_id'] ?? null,
                'related_work_order_task_id' => $context['related_work_order_task_id'] ?? null,
                'note' => $context['note'] ?? null,
                'payload_json' => $context['payload_json'] ?? null,
            ]);

            return $instance->fresh();
        });
    }

    public function confirmVerification(
        ComponentInstance $instance,
        ?ComponentStorageLocation $location = null,
        array $context = [],
    ): ComponentInstance {
        return DB::transaction(function () use ($instance, $location, $context): ComponentInstance {
            $fromStatus = $instance->status;
            $targetLocationId = $location?->id ?? $instance->storage_location_id;

            $instance->forceFill([
                'status' => ComponentInstance::STATUS_IN_STOCK,
                'storage_location_id' => $targetLocationId,
                'last_verified_at' => $context['verified_at'] ?? now(),
                'needs_verification_at' => null,
                'updated_by' => $this->resolveActorId($context['performed_by'] ?? null),
            ])->save();

            $this->events->write($instance, 'verification_confirmed', [
                'performed_by' => $context['performed_by'] ?? null,
                'from_status' => $fromStatus,
                'to_status' => ComponentInstance::STATUS_IN_STOCK,
                'to_storage_location_id' => $targetLocationId,
                'note' => $context['note'] ?? null,
            ]);

            return $instance->fresh();
        });
    }

    public function markDefective(ComponentInstance $instance, array $context = []): ComponentInstance
    {
        $this->assertNotTerminal($instance);

        if (in_array($instance->status, [
            ComponentInstance::STATUS_INSTALLED,
            ComponentInstance::STATUS_DESTRUCTION_PENDING,
        ], true)) {
            throw new InvalidArgumentException('This component cannot be marked defective from its current state.');
        }

        return DB::transaction(function () use ($instance, $context): ComponentInstance {
            $fromStatus = $instance->status;
            $fromAssetId = $instance->current_asset_id;
            $fromStorageLocationId = $instance->storage_location_id;
            $heldByUserId = $instance->held_by_user_id;

            $instance->forceFill([
                'status' => ComponentInstance::STATUS_DEFECTIVE,
                'current_asset_id' => null,
                'held_by_user_id' => null,
                'transfer_started_at' => null,
                'updated_by' => $this->resolveActorId($context['performed_by'] ?? null),
            ])->save();

            $this->events->write($instance, 'marked_defective', [
                'performed_by' => $context['performed_by'] ?? null,
                'from_status' => $fromStatus,
                'to_status' => ComponentInstance::STATUS_DEFECTIVE,
                'from_asset_id' => $fromAssetId,
                'from_storage_location_id' => $fromStorageLocationId,
                'to_storage_location_id' => $instance->storage_location_id,
                'held_by_user_id' => $heldByUserId,
                'note' => $context['note'] ?? null,
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

        return DB::transaction(function () use ($instance, $location, $context): ComponentInstance {
            $fromStatus = $instance->status;
            $fromStorageLocationId = $instance->storage_location_id;

            $instance->forceFill([
                'status' => ComponentInstance::STATUS_DESTRUCTION_PENDING,
                'current_asset_id' => null,
                'storage_location_id' => $location?->id,
                'held_by_user_id' => null,
                'transfer_started_at' => null,
                'updated_by' => $this->resolveActorId($context['performed_by'] ?? null),
            ])->save();

            $this->events->write($instance, 'marked_destruction_pending', [
                'performed_by' => $context['performed_by'] ?? null,
                'from_status' => $fromStatus,
                'to_status' => ComponentInstance::STATUS_DESTRUCTION_PENDING,
                'from_storage_location_id' => $fromStorageLocationId,
                'to_storage_location_id' => $location?->id,
                'note' => $context['note'] ?? null,
            ]);

            return $instance->fresh();
        });
    }

    public function markDestroyed(
        ComponentInstance $instance,
        array $context = [],
    ): ComponentInstance {
        return DB::transaction(function () use ($instance, $context): ComponentInstance {
            $fromStatus = $instance->status;

            $instance->forceFill([
                'status' => ComponentInstance::STATUS_DESTROYED_RECYCLED,
                'current_asset_id' => null,
                'held_by_user_id' => null,
                'transfer_started_at' => null,
                'destroyed_at' => $context['destroyed_at'] ?? now(),
                'updated_by' => $this->resolveActorId($context['performed_by'] ?? null),
            ])->save();

            $this->events->write($instance, 'destroyed_recycled', [
                'performed_by' => $context['performed_by'] ?? null,
                'from_status' => $fromStatus,
                'to_status' => ComponentInstance::STATUS_DESTROYED_RECYCLED,
                'to_storage_location_id' => $instance->storage_location_id,
                'note' => $context['note'] ?? null,
                'payload_json' => $context['payload_json'] ?? null,
            ]);

            return $instance->fresh();
        });
    }

    protected function assertNotTerminal(ComponentInstance $instance): void
    {
        if (in_array($instance->status, [
            ComponentInstance::STATUS_DESTROYED_RECYCLED,
            ComponentInstance::STATUS_SOLD_RETURNED,
        ], true)) {
            throw new InvalidArgumentException('Component is already in a terminal state.');
        }
    }

    protected function assertTrayHolderCanInstall(ComponentInstance $instance, User|int|null $performedBy = null): void
    {
        if ($instance->status !== ComponentInstance::STATUS_IN_TRANSFER || !$instance->held_by_user_id) {
            return;
        }

        $actorId = $this->resolveActorId($performedBy);

        if ($actorId !== $instance->held_by_user_id) {
            throw new InvalidArgumentException('Tray components can only be installed by the user who currently holds them.');
        }
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

        return $attributes;
    }

    protected function resolveInstanceCompanyId(array $attributes, ?int $actorId): ?int
    {
        if (array_key_exists('company_id', $attributes) && $attributes['company_id'] !== null && $attributes['company_id'] !== '') {
            return (int) $attributes['company_id'];
        }

        foreach (['current_asset_id', 'source_asset_id'] as $assetKey) {
            if (!empty($attributes[$assetKey])) {
                $companyId = Asset::query()
                    ->whereKey($attributes[$assetKey])
                    ->value('company_id');

                if ($companyId) {
                    return (int) $companyId;
                }
            }
        }

        if ($actorId) {
            $companyId = User::query()->whereKey($actorId)->value('company_id');

            if ($companyId) {
                return (int) $companyId;
            }
        }

        if ($this->requiresExplicitCompanyScope()) {
            throw new InvalidArgumentException('A company scope is required for tracked components when full multiple company support is enabled.');
        }

        return null;
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
