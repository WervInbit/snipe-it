<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\User;
use Database\Seeders\Concerns\GuardsDisposableDataSeeding;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProductionDemoUserSeeder extends Seeder
{
    use GuardsDisposableDataSeeding;

    private const PASSWORD = 'password';

    public function run(): void
    {
        $this->assertDisposableDataSeedingAllowed();

        $this->call(ProductionPermissionGroupSeeder::class);

        $admin = $this->upsertUser([
            'username' => 'admin',
            'email' => 'admin@example.test',
            'first_name' => 'Production',
            'last_name' => 'Admin',
            'permissions' => ['superuser' => 1],
            'groups' => ['Admin'],
        ]);

        $this->upsertUser([
            'username' => 'demo_admin',
            'email' => 'demo.admin@example.test',
            'first_name' => 'Demo',
            'last_name' => 'Admin',
            'groups' => ['Admin'],
            'created_by' => $admin->id,
        ]);

        $this->upsertUser([
            'username' => 'demo_supervisor',
            'email' => 'demo.supervisor@example.test',
            'first_name' => 'Demo',
            'last_name' => 'Supervisor',
            'groups' => ['Supervisor'],
            'created_by' => $admin->id,
        ]);

        $this->upsertUser([
            'username' => 'demo_senior_refurbisher',
            'email' => 'demo.senior.refurbisher@example.test',
            'first_name' => 'Demo',
            'last_name' => 'Senior Refurbisher',
            'groups' => ['Senior Refurbisher'],
            'created_by' => $admin->id,
        ]);

        $this->upsertUser([
            'username' => 'demo_refurbisher',
            'email' => 'demo.refurbisher@example.test',
            'first_name' => 'Demo',
            'last_name' => 'Refurbisher',
            'groups' => ['Refurbisher'],
            'created_by' => $admin->id,
        ]);
    }

    /**
     * @param array{
     *     username:string,
     *     email:string,
     *     first_name:string,
     *     last_name:string,
     *     permissions?:array<string,int>,
     *     groups?:array<int,string>,
     *     created_by?:int
     * } $payload
     */
    private function upsertUser(array $payload): User
    {
        $user = User::withTrashed()
            ->where('username', $payload['username'])
            ->first() ?? new User(['username' => $payload['username']]);

        if ($user->trashed()) {
            $user->restore();
        }

        $user->fill([
            'email' => $payload['email'],
            'first_name' => $payload['first_name'],
            'last_name' => $payload['last_name'],
            'password' => Hash::make(self::PASSWORD),
            'activated' => 1,
        ]);

        if (isset($payload['created_by'])) {
            $user->created_by = $payload['created_by'];
        }

        $user->permissions = json_encode($payload['permissions'] ?? new \stdClass());
        $user->save();

        $groupIds = Group::query()
            ->whereIn('name', $payload['groups'] ?? [])
            ->pluck('id')
            ->all();

        $user->groups()->sync($groupIds);

        return $user;
    }
}
