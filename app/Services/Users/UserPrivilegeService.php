<?php

namespace App\Services\Users;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UserPrivilegeService
{
    /**
     * Reject direct privilege payloads that the actor cannot manage.
     *
     * Group membership remains a superuser-only operation. Direct permission
     * grants follow the same admin boundary as the user permissions UI.
     *
     * @throws AuthorizationException
     */
    public function authorizeSubmittedPrivileges(
        User $actor,
        bool $permissionsSubmitted,
        bool $groupsSubmitted
    ): void {
        if ($permissionsSubmitted && ! Gate::forUser($actor)->allows('admin')) {
            throw new AuthorizationException('You are not authorized to manage user permissions.');
        }

        if ($groupsSubmitted && ! $actor->isSuperUser()) {
            throw new AuthorizationException('You are not authorized to manage user groups.');
        }
    }

    public function canManageDirectPermissions(User $actor, User $target): bool
    {
        return Gate::forUser($actor)->allows('admin')
            && Gate::forUser($actor)->allows('canEditAuthFields', $target);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function permissionsForWrite(User $actor, mixed $submitted, ?User $target = null): array
    {
        if (is_string($submitted)) {
            $submitted = json_decode($submitted, true);
        }

        if (! is_array($submitted)) {
            throw ValidationException::withMessages([
                'permissions' => ['The permissions field must be a JSON object.'],
            ]);
        }

        $existing = $target ? (array) $target->decodePermissions() : [];

        if (! $actor->isSuperUser()) {
            $this->preservePermission($submitted, $existing, 'superuser');
        }

        if (! $actor->isSuperUser() && ! $actor->hasAccess('admin')) {
            $this->preservePermission($submitted, $existing, 'admin');
        }

        return $submitted;
    }

    /**
     * @param array<string, mixed> $submitted
     * @param array<string, mixed> $existing
     */
    private function preservePermission(array &$submitted, array $existing, string $permission): void
    {
        if (array_key_exists($permission, $existing)) {
            $submitted[$permission] = $existing[$permission];

            return;
        }

        unset($submitted[$permission]);
    }
}
