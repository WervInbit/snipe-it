<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        $user = User::where('username', 'admin')->first() ?? User::factory()->firstAdmin()->create();

        $permissions = array_merge((array) json_decode($user->permissions ?? '{}', true), [
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
