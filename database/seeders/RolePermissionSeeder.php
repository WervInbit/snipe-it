<?php

namespace Database\Seeders;

use App\Models\User;

class RolePermissionSeeder extends DestructiveFixtureSeeder
{
    protected function seedFixtures(): void
    {
        $user = User::where('username', 'admin')->first() ?? User::factory()->firstAdmin()->create();

        $permissions = array_merge((array) json_decode($user->permissions ?? '{}', true), [
            'assets.view'   => '1',
            'scanning'      => '1',
            'tests.execute' => '1',
            'assets.create' => '1',
            'audits.view'   => '1',
            'config.manage' => '1',
        ]);

        $user->permissions = json_encode($permissions);
        $user->save();
    }
}
