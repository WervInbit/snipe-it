<?php

namespace App\Services\Assets;

use App\Models\Statuslabel;
use App\Models\StatusLabelAccessRule;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class StatusLabelAccessService
{
    public function canView(User $user, Statuslabel $status): bool
    {
        return $this->allows($user, $status, 'view_value');
    }

    public function canSelect(User $user, Statuslabel $status): bool
    {
        return $this->canView($user, $status)
            && $this->allows($user, $status, 'select_value');
    }

    /**
     * @return array<int, int>
     */
    public function visibleIdsFor(User $user): array
    {
        return $this->allowedIdsFor($user, 'view');
    }

    /**
     * @return array<int, int>
     */
    public function selectableIdsFor(User $user): array
    {
        return $this->allowedIdsFor($user, 'select');
    }

    private function allows(User $user, Statuslabel $status, string $column): bool
    {
        if ($user->isSuperUser() || $user->isAdmin() || !Schema::hasTable('status_label_access_rules')) {
            return true;
        }

        $rules = StatusLabelAccessRule::query()
            ->where('status_label_id', $status->id)
            ->where($column, '!=', StatusLabelAccessRule::INHERIT);

        if (!(clone $rules)->exists()) {
            return true;
        }

        $direct = (clone $rules)
            ->where('subject_type', StatusLabelAccessRule::SUBJECT_USER)
            ->where('subject_id', $user->id)
            ->value($column);

        if ($direct !== null) {
            return (int) $direct === StatusLabelAccessRule::ALLOW;
        }

        $groupIds = $user->groups->pluck('id');
        if ($groupIds->isEmpty()) {
            return false;
        }

        return (clone $rules)
            ->where('subject_type', StatusLabelAccessRule::SUBJECT_GROUP)
            ->whereIn('subject_id', $groupIds)
            ->where($column, StatusLabelAccessRule::ALLOW)
            ->exists();
    }

    /**
     * @return array<int, int>
     */
    private function allowedIdsFor(User $user, string $capability): array
    {
        if ($user->isSuperUser() || $user->isAdmin() || !Schema::hasTable('status_label_access_rules')) {
            return Statuslabel::query()->pluck('id')->map(fn ($id): int => (int) $id)->all();
        }

        return Statuslabel::query()
            ->get()
            ->filter(fn (Statuslabel $status): bool => $capability === 'select'
                ? $this->canSelect($user, $status)
                : $this->canView($user, $status))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
}
