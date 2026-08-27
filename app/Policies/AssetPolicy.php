<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Asset;

class AssetPolicy extends CheckoutablePermissionsPolicy
{
    protected function columnName()
    {
        return 'assets';
    }

    protected function deniedAbilities(): array
    {
        return ['checkout', 'checkin', 'audit'];
    }

    public function checkout(User $user, $item = null)
    {
        return false;
    }

    public function checkin(User $user, $item = null)
    {
        return false;
    }

    public function audit(User $user, Asset $asset = null)
    {
        return false;
    }

    public function viewFiles(User $user, $item = null)
    {
        return $user->hasAccess('assets.files.view');
    }

    public function createFiles(User $user, $item = null)
    {
        return $user->hasAccess('assets.files.upload');
    }

    public function deleteFiles(User $user, $item = null)
    {
        return $user->hasAccess('assets.files.manage');
    }

    public function uploadImages(User $user, $item = null): bool
    {
        return $user->hasAccess('assets.images.upload')
            || $user->hasAccess('senior-refurbisher')
            || $user->hasAccess('refurbisher');
    }

    public function manageImages(User $user, $item = null): bool
    {
        return $user->hasAccess('assets.images.manage')
            || $user->hasAccess('senior-refurbisher');
    }
}
