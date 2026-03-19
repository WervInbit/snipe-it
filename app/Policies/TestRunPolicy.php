<?php

namespace App\Policies;

use App\Models\TestRun;
use App\Models\User;

class TestRunPolicy
{
    public function update(User $user, TestRun $testRun): bool
    {
        if ($user->hasAccess('supervisor') || $user->hasAccess('admin')) {
            return true;
        }

        if ($testRun->asset && $user->can('update', $testRun->asset)) {
            return true;
        }

        if ($testRun->user_id === $user->id && $user->hasAccess('tests.execute')) {
            return true;
        }

        if ($user->hasAccess('refurbisher') || $user->hasAccess('senior-refurbisher')) {
            return $testRun->user_id === $user->id;
        }

        return false;
    }

    public function delete(User $user, TestRun $testRun): bool
    {
        return $user->hasAccess('supervisor') || $user->hasAccess('admin');
    }
}
