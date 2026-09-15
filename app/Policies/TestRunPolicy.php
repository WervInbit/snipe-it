<?php

namespace App\Policies;

use App\Models\TestRun;
use App\Models\User;

class TestRunPolicy
{
    public function update(User $user, TestRun $testRun): bool
    {
        $testRun->loadMissing(['asset', 'profile']);

        return ($user->isAdmin() || $user->hasAccess('tests.edit_runs'))
            && $testRun->asset
            && $user->can('view', $testRun->asset)
            && (!$testRun->profile || $testRun->profile->canBeExecutedBy($user));
    }

    public function delete(User $user, TestRun $testRun): bool
    {
        return $user->hasAccess('tests.delete')
            || $user->hasAccess('supervisor')
            || $user->hasAccess('admin');
    }
}
