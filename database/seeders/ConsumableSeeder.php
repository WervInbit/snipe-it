<?php

namespace Database\Seeders;

use App\Models\Consumable;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConsumableSeeder extends Seeder
{
    public function run()
    {
        DB::table('consumables_users')->delete();
        DB::statement('ALTER TABLE consumables_users AUTO_INCREMENT = 1');
        Consumable::query()->delete();
        DB::statement('ALTER TABLE consumables AUTO_INCREMENT = 1');

        $admin = User::where('permissions->superuser', '1')->first() ?? User::factory()->firstAdmin()->create();

        Consumable::factory()->count(1)->cardstock()->create(['created_by' => $admin->id]);
        Consumable::factory()->count(1)->paper()->create(['created_by' => $admin->id]);
        Consumable::factory()->count(1)->ink()->create(['created_by' => $admin->id]);
    }
}
