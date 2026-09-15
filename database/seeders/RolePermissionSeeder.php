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
            'tests.execute.senior' => '1',
            'tests.execute.supervisor' => '1',
            'tests.edit_runs' => '1',
            'tests.start_new_run' => '1',
            'tests.override_dependencies' => '1',
            'assets.override_sale_readiness' => '1',
            'assets.create' => '1',
            'audits.view'   => '1',
            'config.manage' => '1',
        ]);

        $user->permissions = json_encode($permissions);
        $user->save();
    }
}
