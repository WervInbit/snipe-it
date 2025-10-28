<?php

namespace Database\Seeders;

use App\Models\Statuslabel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class StatuslabelSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Statuslabel::truncate();
        Schema::enableForeignKeyConstraints();

        $admin = User::where('permissions->superuser', '1')->first() ?? User::factory()->firstAdmin()->create();

        $labels = [
            [
                'name' => 'Stand-by',
                'notes' => 'Wacht op intake of triage.',
                'deployable' => 0,
                'pending' => 1,
                'archived' => 0,
                'default_label' => 1,
                'show_in_nav' => 1,
                'color' => '#1abc9c',
            ],
            [
                'name' => 'Being Processed',
                'notes' => 'Actief in test-, wipe- of herstelproces.',
                'deployable' => 0,
                'pending' => 1,
                'archived' => 0,
                'default_label' => 0,
                'show_in_nav' => 1,
                'color' => '#3498db',
            ],
            [
                'name' => 'QA Hold',
                'notes' => 'Geblokkeerd tot accessoires of cosmetica gereed zijn.',
                'deployable' => 0,
                'pending' => 1,
                'archived' => 0,
                'default_label' => 0,
                'show_in_nav' => 1,
                'color' => '#9b59b6',
            ],
            [
                'name' => 'Ready for Sale',
                'notes' => 'Volledig getest en klaar voor verkoop.',
                'deployable' => 1,
                'pending' => 0,
                'archived' => 0,
                'default_label' => 0,
                'show_in_nav' => 1,
                'color' => '#2ecc71',
            ],
            [
                'name' => 'Sold',
                'notes' => 'Order afgerond en uit voorraad.',
                'deployable' => 0,
                'pending' => 0,
                'archived' => 1,
                'default_label' => 0,
                'show_in_nav' => 1,
                'color' => '#e67e22',
            ],
            [
                'name' => 'Broken / Parts',
                'notes' => 'Niet verkoopbaar; gebruikt voor onderdelen of referentie.',
                'deployable' => 0,
                'pending' => 0,
                'archived' => 0,
                'default_label' => 0,
                'show_in_nav' => 1,
                'color' => '#e74c3c',
            ],
            [
                'name' => 'Internal Use',
                'notes' => 'Beschikbaar voor interne teams of labopstellingen.',
                'deployable' => 0,
                'pending' => 0,
                'archived' => 0,
                'default_label' => 0,
                'show_in_nav' => 1,
                'color' => '#34495e',
            ],
            [
                'name' => 'Archived',
                'notes' => 'Gearchiveerd voor naslag, niet actief in omloop.',
                'deployable' => 0,
                'pending' => 0,
                'archived' => 1,
                'default_label' => 0,
                'show_in_nav' => 1,
                'color' => '#7f8c8d',
            ],
            [
                'name' => 'Returned / RMA',
                'notes' => 'Retour ontvangen; wacht op herinspectie.',
                'deployable' => 0,
                'pending' => 1,
                'archived' => 0,
                'default_label' => 0,
                'show_in_nav' => 1,
                'color' => '#f1c40f',
            ],
        ];

        foreach ($labels as $data) {
            Statuslabel::create(array_merge($data, [
                'created_by' => $admin->id,
            ]));
        }
    }
}
