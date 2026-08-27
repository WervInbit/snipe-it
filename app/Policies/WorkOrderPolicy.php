<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkOrder;

class WorkOrderPolicy extends SnipePermissionsPolicy
{
    public function before(User $user, $ability, $item)
    {
        if ($ability === 'viewPortal') {
            return null;
        }

        return parent::before($user, $ability, $item);
    }

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

    public function viewPortal(User $user, $item = null)
    {
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
