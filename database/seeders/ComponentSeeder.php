<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Component;
use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ComponentSeeder extends Seeder
{
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        Component::truncate();
        DB::table('components_assets')->truncate();
        Schema::enableForeignKeyConstraints();

        if (! Company::count()) {
            $this->call(CompanySeeder::class);
        }

        $companyIds = Company::all()->pluck('id');

        if (! Location::count()) {
            $this->call(LocationSeeder::class);
        }

        $locationIds = Location::all()->pluck('id');

        Component::factory()->ramCrucial4()->create([
            'company_id' => $companyIds->random(),
            'location_id' => $locationIds->random(),
        ]);
        Component::factory()->ramCrucial8()->create([
            'company_id' => $companyIds->random(),
            'location_id' => $locationIds->random(),
        ]);
        Component::factory()->ssdCrucial120()->create([
            'company_id' => $companyIds->random(),
            'location_id' => $locationIds->random(),
        ]);
        Component::factory()->ssdCrucial240()->create([
            'company_id' => $companyIds->random(),
            'location_id' => $locationIds->random(),
        ]);
    }
}
