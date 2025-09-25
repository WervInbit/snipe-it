<?php

namespace App\Policies;

use App\Models\AttributeOption;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttributeOptionPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasAccess('superuser') || $user->hasAccess('admin')) {
            return true;
        }

        return null;
    }

    public function update(User $user, AttributeOption $option): bool
    {
        return false;
    }

    public function delete(User $user, AttributeOption $option): bool
    {
        return false;
    }
}
