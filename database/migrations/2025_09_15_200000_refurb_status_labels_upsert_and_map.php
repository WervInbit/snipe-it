<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $labels = [
            ['name' => 'Intake / New Arrival','deployable' => 0,'pending' => 1,'archived' => 0,'show_in_nav' => 1,'default_label' => 1,'notes' => 'Awaiting initial processing after arrival'],
            ['name' => 'In Testing','deployable' => 0,'pending' => 1,'archived' => 0,'show_in_nav' => 1,'default_label' => 0,'notes' => 'Undergoing diagnostics/QA testing'],
            ['name' => 'Tested – OK','deployable' => 0,'pending' => 1,'archived' => 0,'show_in_nav' => 1,'default_label' => 0,'notes' => 'Testing passed; awaiting sales approval'],
            ['name' => 'Needs Repair','deployable' => 0,'pending' => 1,'archived' => 0,'show_in_nav' => 1,'default_label' => 0,'notes' => 'Testing failed; requires repair'],
            ['name' => 'Under Repair','deployable' => 0,'pending' => 1,'archived' => 0,'show_in_nav' => 1,'default_label' => 0,'notes' => 'Actively being repaired/refurbished'],
            ['name' => 'Ready for Sale','deployable' => 1,'pending' => 0,'archived' => 0,'show_in_nav' => 1,'default_label' => 0,'notes' => 'Approved and sellable'],
            ['name' => 'Sold to Customer','deployable' => 0,'pending' => 0,'archived' => 1,'show_in_nav' => 1,'default_label' => 0,'notes' => 'Sold and removed from active inventory'],
            ['name' => 'Returned – Pending','deployable' => 0,'pending' => 1,'archived' => 0,'show_in_nav' => 1,'default_label' => 0,'notes' => 'Returned by customer; awaiting re-test'],
            ['name' => 'Broken / For Parts','deployable' => 0,'pending' => 0,'archived' => 0,'show_in_nav' => 1,'default_label' => 0,'notes' => 'Unsellable; for parts/harvest only'],
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
            'Pending' => 'Intake / New Arrival',
            'Out for Diagnostics' => 'In Testing',
            'Out for Repair' => 'Under Repair',
            'Being Refurbished' => 'Under Repair',
            'Broken - Not Fixable' => 'Broken / For Parts',
            'Broken/Spare Parts' => 'Broken / For Parts',
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

        if (isset($ids['Intake / New Arrival'])) {
            DB::table('status_labels')->update(['default_label' => 0]);
            DB::table('status_labels')->where('id', $ids['Intake / New Arrival'])->update(['default_label' => 1]);
        }
    }

    public function down(): void
    {
        // No-op
    }
};

