<?php

namespace Database\Seeders;

use App\Models\Group;
use Illuminate\Database\Seeder;

class ProductionPermissionGroupSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->permissionGroups() as $name => $permissions) {
            Group::updateOrCreate(
                ['name' => $name],
                ['permissions' => json_encode($permissions)]
            );
        }
    }

    /**
     * @return array<string,array<string,int>>
     */
    private function permissionGroups(): array
    {
        return [
            'Refurbisher' => [
                'assets.view' => 1,
                'scanning' => 1,
            ],
            'Senior Refurbisher' => [
                'assets.view' => 1,
                'scanning' => 1,
                'tests.execute' => 1,
            ],
            'Supervisor' => [
                'assets.view' => 1,
                'scanning' => 1,
                'tests.execute' => 1,
                'assets.sale_transition' => 1,
                'assets.create' => 1,
                'assets.delete' => 1,
                'tests.delete' => 1,
            ],
            'Admin' => [
                'admin' => 1,
                'assets.view' => 1,
                'scanning' => 1,
                'tests.execute' => 1,
                'assets.sale_transition' => 1,
                'assets.create' => 1,
                'assets.delete' => 1,
                'tests.delete' => 1,
                'audits.view' => 1,
                'config.qr_tooltips' => 1,
            ],
        ];
    }
}
