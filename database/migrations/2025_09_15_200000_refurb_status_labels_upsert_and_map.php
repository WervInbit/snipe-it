<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $labels = [
            ['name' => 'Stand-by','deployable' => 0,'pending' => 1,'archived' => 0,'show_in_nav' => 1,'default_label' => 1,'notes' => 'Wacht op intake of triage.','color' => '#1abc9c'],
            ['name' => 'Being Processed','deployable' => 0,'pending' => 1,'archived' => 0,'show_in_nav' => 1,'default_label' => 0,'notes' => 'Actief in test-, wipe- of herstelproces.','color' => '#3498db'],
            ['name' => 'QA Hold','deployable' => 0,'pending' => 1,'archived' => 0,'show_in_nav' => 1,'default_label' => 0,'notes' => 'Geblokkeerd tot accessoires of cosmetica gereed zijn.','color' => '#9b59b6'],
            ['name' => 'Ready for Sale','deployable' => 1,'pending' => 0,'archived' => 0,'show_in_nav' => 1,'default_label' => 0,'notes' => 'Volledig getest en klaar voor verkoop.','color' => '#2ecc71'],
            ['name' => 'Sold','deployable' => 0,'pending' => 0,'archived' => 1,'show_in_nav' => 1,'default_label' => 0,'notes' => 'Order afgerond en uit voorraad.','color' => '#e67e22'],
            ['name' => 'Broken / Parts','deployable' => 0,'pending' => 0,'archived' => 0,'show_in_nav' => 1,'default_label' => 0,'notes' => 'Niet verkoopbaar; gebruikt voor onderdelen of referentie.','color' => '#e74c3c'],
            ['name' => 'Internal Use','deployable' => 0,'pending' => 0,'archived' => 0,'show_in_nav' => 1,'default_label' => 0,'notes' => 'Beschikbaar voor interne teams of labopstellingen.','color' => '#34495e'],
            ['name' => 'Archived','deployable' => 0,'pending' => 0,'archived' => 1,'show_in_nav' => 1,'default_label' => 0,'notes' => 'Gearchiveerd voor naslag, niet actief in omloop.','color' => '#7f8c8d'],
            ['name' => 'Returned / RMA','deployable' => 0,'pending' => 1,'archived' => 0,'show_in_nav' => 1,'default_label' => 0,'notes' => 'Retour ontvangen; wacht op herinspectie.','color' => '#f1c40f'],
        ];

        if (DB::getSchemaBuilder()->hasColumn('status_labels', 'default_label')) {
            DB::table('status_labels')->update(['default_label' => 0]);
        }

        foreach ($labels as $data) {
            $existing = DB::table('status_labels')->where('name', $data['name'])->first();
            if ($existing) {
                DB::table('status_labels')->where('id', $existing->id)->update(array_merge($data, ['updated_at' => $now]));
            } else {
                DB::table('status_labels')->insert(array_merge($data, ['created_at' => $now, 'updated_at' => $now]));
            }
        }

        $ids = DB::table('status_labels')->whereIn('name', array_column($labels, 'name'))->pluck('id', 'name');
        $map = [
            'Ready to Deploy' => 'Ready for Sale',
            'Pending' => 'Stand-by',
            'Intake / New Arrival' => 'Stand-by',
            'In Testing' => 'Being Processed',
            'Needs Repair' => 'Broken / Parts',
            'Under Repair' => 'Being Processed',
            'Tested - OK' => 'Ready for Sale',
            'Tested – OK' => 'Ready for Sale',
            'Out for Diagnostics' => 'Being Processed',
            'Out for Repair' => 'Being Processed',
            'Being Refurbished' => 'Being Processed',
            'Sold to Customer' => 'Sold',
            'Sold / Shipped' => 'Sold',
            'Returned - Pending' => 'Returned / RMA',
            'Returned – Pending' => 'Returned / RMA',
            'Broken - Not Fixable' => 'Broken / Parts',
            'Broken/Spare Parts' => 'Broken / Parts',
            'Broken / For Parts' => 'Broken / Parts',
        ];

        foreach ($map as $old => $new) {
            $oldLabel = DB::table('status_labels')->where('name', $old)->first();
            $newId = $ids[$new] ?? null;
            if ($oldLabel && $newId) {
                DB::table('assets')->where('status_id', $oldLabel->id)->update(['status_id' => $newId]);
                $stillUsed = DB::table('assets')->where('status_id', $oldLabel->id)->exists();
                if (!$stillUsed && DB::getSchemaBuilder()->hasColumn('status_labels', 'deleted_at')) {
                    DB::table('status_labels')->where('id', $oldLabel->id)->update(['deleted_at' => $now]);
                }
            }
        }

        if (isset($ids['Stand-by'])) {
            DB::table('status_labels')->update(['default_label' => 0]);
            DB::table('status_labels')->where('id', $ids['Stand-by'])->update(['default_label' => 1]);
        }
    }

    public function down(): void
    {
        // No-op
    }
};










