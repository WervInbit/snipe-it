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
        return $user->hasAccess('attributes.view');
    }

    public function view(User $user, AttributeDefinition $definition): bool
    {
        return $user->hasAccess('attributes.view');
    }

    public function create(User $user): bool
    {
        return $user->hasAccess('attributes.create');
    }

    public function update(User $user, AttributeDefinition $definition): bool
    {
        return $user->hasAccess('attributes.edit');
    }

    public function delete(User $user, ?AttributeDefinition $definition = null): bool
    {
        return $user->hasAccess('attributes.delete');
    }

    public function manageLifecycle(User $user, ?AttributeDefinition $definition = null): bool
    {
        return $user->hasAccess('attributes.lifecycle');
    }
}
