<?php

namespace App\Policies;

use App\Models\Maintenance;
use App\Models\User;

class MaintenancePolicy extends SnipePermissionsPolicy
{
    protected function columnName()
    {
        return 'assets';
    }

    protected function deniedAbilities(): array
    {
        return ['create', 'update', 'delete', 'manage', 'createFiles', 'deleteFiles'];
    }

    public function view(User $user, $item = null)
    {
        return $item instanceof Maintenance
            && $item->asset
            && $user->can('view', $item->asset);
    }

    public function update(User $user, $item = null)
    {
        return false;
    }
}
