<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkOrder;

class WorkOrderPolicy extends SnipePermissionsPolicy
{
    protected function columnName()
    {
        return 'workorders';
    }

    public function viewAny(User $user)
    {
        return $user->hasAccess('workorders.view');
    }

    public function view(User $user, $item = null)
    {
        if ($user->hasAccess('workorders.view')) {
            return true;
        }

        return $item instanceof WorkOrder
            && $user->hasAccess('portal.view')
            && $item->isVisibleTo($user);
    }

    public function create(User $user)
    {
        return $user->hasAccess('workorders.create');
    }

    public function update(User $user, $item = null)
    {
        return $user->hasAccess('workorders.update');
    }

    public function manageVisibility(User $user, $item = null)
    {
        return $user->hasAccess('workorders.manage_visibility');
    }

    public function manage(User $user, $item = null)
    {
        return $user->hasAccess('workorders.update');
    }
}
