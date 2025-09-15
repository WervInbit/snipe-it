<?php

namespace Database\Seeders;

use App\Models\Statuslabel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class StatuslabelSeeder extends Seeder
{
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        Statuslabel::truncate();
        Schema::enableForeignKeyConstraints();

        $admin = User::where('permissions->superuser', '1')->first() ?? User::factory()->firstAdmin()->create();

        // Inbit refurbishment workflow statuses
        // Intake (default) — Pending
        Statuslabel::create([
            'name' => 'Intake / New Arrival',
            'notes' => 'Awaiting initial processing after arrival',
            'deployable' => 0,
            'pending' => 1,
            'archived' => 0,
            'default_label' => 1,
            'show_in_nav' => 1,
            'created_by' => $admin->id,
        ]);

        // In Testing — Pending
        Statuslabel::create([
            'name' => 'In Testing',
            'notes' => 'Undergoing diagnostics/QA testing',
            'deployable' => 0,
            'pending' => 1,
            'archived' => 0,
            'default_label' => 0,
            'show_in_nav' => 1,
            'created_by' => $admin->id,
        ]);

        // Tested – OK — Pending (awaiting sales approval)
        Statuslabel::create([
            'name' => 'Tested – OK',
            'notes' => 'Testing passed; awaiting sales approval',
            'deployable' => 0,
            'pending' => 1,
            'archived' => 0,
            'default_label' => 0,
            'show_in_nav' => 1,
            'created_by' => $admin->id,
        ]);

        // Needs Repair — Pending
        Statuslabel::create([
            'name' => 'Needs Repair',
            'notes' => 'Testing failed; requires repair',
            'deployable' => 0,
            'pending' => 1,
            'archived' => 0,
            'default_label' => 0,
            'show_in_nav' => 1,
            'created_by' => $admin->id,
        ]);

        // Under Repair — Pending
        Statuslabel::create([
            'name' => 'Under Repair',
            'notes' => 'Actively being repaired/refurbished',
            'deployable' => 0,
            'pending' => 1,
            'archived' => 0,
            'default_label' => 0,
            'show_in_nav' => 1,
            'created_by' => $admin->id,
        ]);

        // Ready for Sale — Deployable
        Statuslabel::create([
            'name' => 'Ready for Sale',
            'notes' => 'Approved and sellable',
            'deployable' => 1,
            'pending' => 0,
            'archived' => 0,
            'default_label' => 0,
            'show_in_nav' => 1,
            'created_by' => $admin->id,
        ]);

        // Sold to Customer — Archived
        Statuslabel::create([
            'name' => 'Sold to Customer',
            'notes' => 'Sold and removed from active inventory',
            'deployable' => 0,
            'pending' => 0,
            'archived' => 1,
            'default_label' => 0,
            'show_in_nav' => 1,
            'created_by' => $admin->id,
        ]);

        // Returned – Pending — Pending
        Statuslabel::create([
            'name' => 'Returned – Pending',
            'notes' => 'Returned by customer; awaiting re-test',
            'deployable' => 0,
            'pending' => 1,
            'archived' => 0,
            'default_label' => 0,
            'show_in_nav' => 1,
            'created_by' => $admin->id,
        ]);

        // Broken / For Parts — Undeployable (all flags 0)
        Statuslabel::create([
            'name' => 'Broken / For Parts',
            'notes' => 'Unsellable; for parts/harvest only',
            'deployable' => 0,
            'pending' => 0,
            'archived' => 0,
            'default_label' => 0,
            'show_in_nav' => 1,
            'created_by' => $admin->id,
        ]);
    }
}
