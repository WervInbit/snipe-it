<?php

namespace App\Services\Users;

use App\Models\Accessory;
use App\Models\Asset;
use App\Models\Consumable;
use App\Models\License;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class PrintableUserInventoryService
{
    /**
     * Load only inventory relations the actor may view. Denied relations are
     * explicitly set to empty collections so Blade cannot lazy-load them.
     */
    public function findFor(User $actor, int $userId): ?User
    {
        $canViewAssets = $actor->can('view', Asset::class);
        $canViewLicenses = $actor->can('view', License::class);
        $canViewAccessories = $actor->can('view', Accessory::class);
        $canViewConsumables = $actor->can('view', Consumable::class);
        $relations = [];

        if ($canViewAssets) {
            $relations = array_merge($relations, [
                'assets.log' => fn ($query) => $query->withTrashed()
                    ->where('target_type', User::class)
                    ->where('target_id', $userId)
                    ->where('action_type', 'accepted'),
                'assets.assignedAssets.log' => fn ($query) => $query->withTrashed()
                    ->where('target_type', User::class)
                    ->where('target_id', $userId)
                    ->where('action_type', 'accepted'),
                'assets.assignedAssets.defaultLoc',
                'assets.assignedAssets.location',
                'assets.assignedAssets.model.category',
                'assets.defaultLoc',
                'assets.location',
                'assets.model.category',
            ]);
        }

        if ($canViewLicenses) {
            $relations[] = 'licenses.category';
        }

        if ($canViewAccessories) {
            $relations = array_merge($relations, [
                'accessories.log' => fn ($query) => $query->withTrashed()
                    ->where('target_type', User::class)
                    ->where('target_id', $userId)
                    ->where('action_type', 'accepted'),
                'accessories.category',
                'accessories.manufacturer',
            ]);
        }

        if ($canViewConsumables) {
            $relations = array_merge($relations, [
                'consumables.log' => fn ($query) => $query->withTrashed()
                    ->where('target_type', User::class)
                    ->where('target_id', $userId)
                    ->where('action_type', 'accepted'),
                'consumables.category',
                'consumables.manufacturer',
            ]);
        }

        $user = User::query()
            ->with($relations)
            ->withTrashed()
            ->find($userId);

        if (! $user) {
            return null;
        }

        foreach ([
            'assets' => $canViewAssets,
            'licenses' => $canViewLicenses,
            'accessories' => $canViewAccessories,
            'consumables' => $canViewConsumables,
        ] as $relation => $allowed) {
            if (! $allowed) {
                $user->setRelation($relation, new EloquentCollection);
            }
        }

        return $user;
    }
}
