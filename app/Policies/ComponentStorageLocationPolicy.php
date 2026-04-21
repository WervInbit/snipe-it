<?php

namespace App\Policies;

use App\Models\User;

class ComponentStorageLocationPolicy extends SnipePermissionsPolicy
{
    protected function columnName()
    {
        return 'components';
    }

    public function create(User $user)
    {
        return $user->hasAccess('components.manage_storage_locations');
    }

    public function update(User $user, $item = null)
    {
        return $user->hasAccess('components.manage_storage_locations');
    }

    public function delete(User $user, $item = null)
    {
        return $user->hasAccess('components.manage_storage_locations');
    }

    public function manage(User $user, $item = null)
    {
        return $user->hasAccess('components.manage_storage_locations');
    }
}
