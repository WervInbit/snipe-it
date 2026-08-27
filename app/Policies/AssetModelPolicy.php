<?php

namespace App\Policies;

use App\Models\User;

class AssetModelPolicy extends SnipePermissionsPolicy
{
    protected function columnName()
    {
        return 'models';
    }

    public function manageLifecycle(User $user, $item = null)
    {
        return $user->hasAccess('models.manage_lifecycle');
    }

    public function manageSpecificationCleanup(User $user, $item = null)
    {
        return $user->hasAccess('models.manage_specification_cleanup');
    }

    public function delete(User $user, $item = null)
    {
        return $user->hasAccess('models.delete')
            && $user->hasAccess('models.manage_lifecycle');
    }

    public function viewFiles(User $user, $item = null)
    {
        return $user->hasAccess('models.files.view');
    }

    public function createFiles(User $user, $item = null)
    {
        return $user->hasAccess('models.files.upload');
    }

    public function deleteFiles(User $user, $item = null)
    {
        return $user->hasAccess('models.files.manage');
    }
}
