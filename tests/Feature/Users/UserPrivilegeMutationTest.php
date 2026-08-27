<?php

namespace Tests\Feature\Users;

use App\Models\Group;
use App\Models\User;
use Tests\TestCase;

class UserPrivilegeMutationTest extends TestCase
{
    public function testGranularCreatorCannotAssignDirectAdminPermissionThroughWebRequest(): void
    {
        $actor = User::factory()->createUsers()->create();

        $this->actingAs($actor)
            ->post(route('users.store'), array_merge(
                $this->newUserPayload('web-permission-attack'),
                ['permission' => ['admin' => '1']]
            ))
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['username' => 'web-permission-attack']);
    }

    public function testGranularCreatorCannotAssignDirectAdminPermissionThroughApiRequest(): void
    {
        $actor = User::factory()->createUsers()->create();

        $this->actingAsForApi($actor)
            ->postJson(route('api.users.store'), array_merge(
                $this->newUserPayload('api-permission-attack'),
                ['permissions' => ['admin' => '1']]
            ))
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['username' => 'api-permission-attack']);
    }

    public function testNonSuperuserAdminCannotAssignPrivilegedGroupDuringWebCreate(): void
    {
        $actor = User::factory()->admin()->create();
        $privilegedGroup = Group::factory()->create([
            'permissions' => json_encode(['admin' => 1]),
        ]);

        $this->actingAs($actor)
            ->post(route('users.store'), array_merge(
                $this->newUserPayload('web-group-attack'),
                ['groups' => [$privilegedGroup->id]]
            ))
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['username' => 'web-group-attack']);
    }

    public function testNonSuperuserAdminCannotAssignPrivilegedGroupDuringApiCreate(): void
    {
        $actor = User::factory()->admin()->create();
        $privilegedGroup = Group::factory()->create([
            'permissions' => json_encode(['admin' => 1]),
        ]);

        $this->actingAsForApi($actor)
            ->postJson(route('api.users.store'), array_merge(
                $this->newUserPayload('api-group-attack'),
                ['groups' => [$privilegedGroup->id]]
            ))
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['username' => 'api-group-attack']);
    }

    public function testGranularEditorCannotPromoteUserThroughWebRequest(): void
    {
        $actor = User::factory()->editUsers()->create();
        $target = User::factory()->create([
            'first_name' => 'Original',
            'permissions' => json_encode(['assets.view' => 1]),
        ]);

        $this->actingAs($actor)
            ->put(route('users.update', $target), [
                'first_name' => 'Attacker changed profile too',
                'username' => $target->username,
                'permission' => ['admin' => '1'],
            ])
            ->assertForbidden();

        $target->refresh();
        $this->assertSame('Original', $target->first_name);
        $this->assertFalse($target->hasAccess('admin'));
        $this->assertTrue($target->hasAccess('assets.view'));
    }

    public function testGranularEditorCannotPromoteUserThroughApiRequest(): void
    {
        $actor = User::factory()->editUsers()->create();
        $target = User::factory()->create([
            'first_name' => 'Original',
            'permissions' => json_encode(['assets.view' => 1]),
        ]);

        $this->actingAsForApi($actor)
            ->patchJson(route('api.users.update', $target), [
                'first_name' => 'Attacker changed profile too',
                'permissions' => ['admin' => '1'],
            ])
            ->assertForbidden();

        $target->refresh();
        $this->assertSame('Original', $target->first_name);
        $this->assertFalse($target->hasAccess('admin'));
        $this->assertTrue($target->hasAccess('assets.view'));
    }

    public function testWebProfileEditWithoutPermissionFieldsPreservesDirectPermissions(): void
    {
        $actor = User::factory()->editUsers()->create();
        $target = User::factory()->create([
            'first_name' => 'Original',
            'permissions' => json_encode([
                'assets.view' => 1,
                'tests.execute' => 1,
            ]),
        ]);

        $this->actingAs($actor)
            ->put(route('users.update', $target), [
                'first_name' => 'Updated',
                'username' => $target->username,
            ])
            ->assertRedirect();

        $target->refresh();
        $this->assertSame('Updated', $target->first_name);
        $this->assertTrue($target->hasAccess('assets.view'));
        $this->assertTrue($target->hasAccess('tests.execute'));
    }

    public function testApiProfileEditWithoutPermissionFieldsPreservesDirectPermissions(): void
    {
        $actor = User::factory()->editUsers()->create();
        $target = User::factory()->create([
            'first_name' => 'Original',
            'permissions' => json_encode([
                'assets.view' => 1,
                'tests.execute' => 1,
            ]),
        ]);

        $this->actingAsForApi($actor)
            ->patchJson(route('api.users.update', $target), [
                'first_name' => 'Updated',
            ])
            ->assertOk()
            ->assertStatusMessageIs('success');

        $target->refresh();
        $this->assertSame('Updated', $target->first_name);
        $this->assertTrue($target->hasAccess('assets.view'));
        $this->assertTrue($target->hasAccess('tests.execute'));
    }

    public function testInvalidApiGroupsAreRejectedBeforeProfileMutation(): void
    {
        $actor = User::factory()->superuser()->create();
        $target = User::factory()->create(['first_name' => 'Original']);

        $this->actingAsForApi($actor)
            ->patchJson(route('api.users.update', $target), [
                'first_name' => 'Partially changed',
                'groups' => [999999],
            ])
            ->assertOk()
            ->assertStatusMessageIs('error')
            ->assertJsonStructure(['messages']);

        $this->assertSame('Original', $target->fresh()->first_name);
    }

    public function testInvalidApiGroupsAreRejectedBeforeUserCreation(): void
    {
        $actor = User::factory()->superuser()->create();

        $this->actingAsForApi($actor)
            ->postJson(route('api.users.store'), array_merge(
                $this->newUserPayload('invalid-api-group'),
                ['groups' => [999999]]
            ))
            ->assertOk()
            ->assertStatusMessageIs('error')
            ->assertJsonStructure(['messages']);

        $this->assertDatabaseMissing('users', ['username' => 'invalid-api-group']);
    }

    public function testWebSaveFailureDoesNotChangeGroupMembership(): void
    {
        $actor = User::factory()->superuser()->create();
        $takenUsername = User::factory()->create()->username;
        $target = User::factory()->create(['first_name' => 'Original']);
        [$existingGroup, $replacementGroup] = Group::factory()->count(2)->create();
        $target->groups()->sync([$existingGroup->id]);

        $this->actingAs($actor)
            ->from(route('users.edit', $target))
            ->put(route('users.update', $target), [
                'first_name' => 'Partially changed',
                'username' => $takenUsername,
                'groups' => [$replacementGroup->id],
            ])
            ->assertRedirect(route('users.edit', $target))
            ->assertSessionHasErrors('username');

        $target->refresh();
        $this->assertSame('Original', $target->first_name);
        $this->assertTrue($target->groups->contains($existingGroup));
        $this->assertFalse($target->groups->contains($replacementGroup));
    }

    /**
     * @return array<string, mixed>
     */
    private function newUserPayload(string $username): array
    {
        return [
            'first_name' => 'Privilege test',
            'username' => $username,
            'password' => 'Valid-password-123!',
            'password_confirmation' => 'Valid-password-123!',
        ];
    }
}
