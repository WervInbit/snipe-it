<?php

namespace Database\Seeders;

use App\Models\Depreciation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DepreciationSeeder extends Seeder
{
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        Depreciation::truncate();
        Schema::enableForeignKeyConstraints();

        $admin = User::where('permissions->superuser', '1')->first() ?? User::factory()->firstAdmin()->create();

        Depreciation::factory()->count(1)->computer()->create(['created_by' => $admin->id]);
        Depreciation::factory()->count(1)->display()->create(['created_by' => $admin->id]);
        Depreciation::factory()->count(1)->mobilePhones()->create(['created_by' => $admin->id]);
    }
}
