<?php

namespace App\Policies;

use App\Models\User;

class ComponentDefinitionPolicy extends SnipePermissionsPolicy
{
    protected function columnName()
    {
        return 'components';
    }

    public function create(User $user)
    {
        return $user->hasAccess('components.manage_definitions');
    }

    public function update(User $user, $item = null)
    {
        return $user->hasAccess('components.manage_definitions');
    }

    public function delete(User $user, $item = null)
    {
        return $user->hasAccess('components.manage_definitions');
    }

    public function manage(User $user, $item = null)
    {
        return $user->hasAccess('components.manage_definitions');
    }
}
