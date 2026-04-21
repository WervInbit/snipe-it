<?php

namespace App\Policies;

use App\Models\ComponentInstance;
use App\Models\User;

class ComponentInstancePolicy extends SnipePermissionsPolicy
{
    protected function columnName()
    {
        return 'components';
    }

    public function update(User $user, $item = null)
    {
        return $user->hasAccess('components.update') || $user->hasAccess('components.edit');
    }

    public function files(User $user, $item = null)
    {
        return $user->hasAccess('components.update') || $user->hasAccess('components.edit');
    }

    public function manage(User $user, $item = null)
    {
        return $this->update($user, $item);
    }

    public function extract(User $user, ?ComponentInstance $item = null)
    {
        return $user->hasAccess('components.extract');
    }

    public function install(User $user, ?ComponentInstance $item = null)
    {
        return $user->hasAccess('components.install');
    }

    public function move(User $user, ?ComponentInstance $item = null)
    {
        return $user->hasAccess('components.move');
    }

    public function verify(User $user, ?ComponentInstance $item = null)
    {
        return $user->hasAccess('components.verify');
    }
}
