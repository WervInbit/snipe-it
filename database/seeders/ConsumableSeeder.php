<?php

namespace Database\Seeders;

use App\Models\Consumable;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConsumableSeeder extends DestructiveFixtureSeeder
{
    protected function seedFixtures(): void
    {
        Schema::disableForeignKeyConstraints();
        Consumable::truncate();
        DB::table('consumables_users')->truncate();
        Schema::enableForeignKeyConstraints();

        $admin = User::where('permissions->superuser', '1')->first() ?? User::factory()->firstAdmin()->create();

        Consumable::factory()->count(1)->cardstock()->create(['created_by' => $admin->id]);
        Consumable::factory()->count(1)->paper()->create(['created_by' => $admin->id]);
        Consumable::factory()->count(1)->ink()->create(['created_by' => $admin->id]);
    }
}
