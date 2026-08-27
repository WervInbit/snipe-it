<?php

namespace App\Policies;

use App\Models\User;

class ComponentPolicy extends CheckoutablePermissionsPolicy
{
    protected function columnName()
    {
        return 'components';
    }

    public function update(User $user, $item = null)
    {
        return $user->hasAccess('components.update') || $user->hasAccess('components.edit');
    }
}
