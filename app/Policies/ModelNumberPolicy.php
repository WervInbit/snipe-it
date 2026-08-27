<?php

namespace App\Policies;

use App\Models\ModelNumber;
use App\Models\User;

class ModelNumberPolicy extends SnipePermissionsPolicy
{
    protected function columnName()
    {
        return 'models';
    }

    public function view(User $user, $item = null)
    {
        return $item instanceof ModelNumber
            && $item->model
            && $user->can('view', $item->model);
    }

    public function update(User $user, $item = null)
    {
        return $item instanceof ModelNumber
            && $item->model
            && $user->can('update', $item->model);
    }

    public function delete(User $user, $item = null)
    {
        return $user->hasAccess('models.delete')
            && $user->hasAccess('models.manage_lifecycle');
    }
}
