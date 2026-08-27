<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class DepartmentSeeder extends DestructiveFixtureSeeder
{
    protected function seedFixtures(): void
    {
        Schema::disableForeignKeyConstraints();
        Department::truncate();
        Schema::enableForeignKeyConstraints();

        if (! Location::count()) {
            $this->call(LocationSeeder::class);
        }

        $locationIds = Location::all()->pluck('id');

        $admin = User::where('permissions->superuser', '1')->first() ?? User::factory()->firstAdmin()->create();

        foreach ([
            'Refurb Operations',
            'Quality Assurance',
            'Inventory Control',
        ] as $name) {
            Department::factory()->create([
                'name' => $name,
                'location_id' => $locationIds->random(),
                'created_by' => $admin->id,
            ]);
        }
    }
}
