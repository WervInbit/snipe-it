<?php

namespace App\Services\Assets;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class RecentAssetActivityService
{
    public function recordForCurrentUser(Asset $asset): void
    {
        $user = auth()->user();

        if (!$user instanceof User || !Schema::hasTable('user_recent_assets')) {
            return;
        }

        DB::table('user_recent_assets')->updateOrInsert(
            [
                'user_id' => $user->id,
                'asset_id' => $asset->id,
            ],
            ['last_activity_at' => now()]
        );
    }

    /**
     * Return the most recent assets the user can still view.
     *
     * @return Collection<int, Asset>
     */
    public function forUser(User $user, int $limit = 5): Collection
    {
        if (!Schema::hasTable('user_recent_assets') || !Gate::forUser($user)->allows('view', Asset::class)) {
            return collect();
        }

        return Asset::query()
            ->with(['model', 'assetstatus'])
            ->join('user_recent_assets', 'user_recent_assets.asset_id', '=', 'assets.id')
            ->where('user_recent_assets.user_id', $user->id)
            ->orderByDesc('user_recent_assets.last_activity_at')
            ->select('assets.*')
            ->limit($limit)
            ->get()
            ->filter(fn (Asset $asset): bool => Gate::forUser($user)->allows('view', $asset))
            ->values();
    }
}
