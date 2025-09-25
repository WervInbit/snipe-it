<?php

namespace App\Policies;

use App\Models\AttributeDefinition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttributeDefinitionPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasAccess('superuser') || $user->hasAccess('admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, AttributeDefinition $definition): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AttributeDefinition $definition): bool
    {
        return false;
    }

    public function delete(User $user, AttributeDefinition $definition): bool
    {
        return false;
    }
}
