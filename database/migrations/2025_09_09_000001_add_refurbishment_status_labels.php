<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Support\\Facades\\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Being Refurbished: pending type (not deployable, not archived)
        if (! DB::table('status_labels')->where('name', 'Being Refurbished')->exists()) {
            DB::table('status_labels')->insert([
                'name' => 'Being Refurbished',
                'deployable' => 0,
                'pending' => 1,
                'archived' => 0,
                'notes' => 'Asset is currently in the refurbishing process.',
                'color' => '#f0ad4e', // amber
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Broken/Spare Parts: undeployable type (not deployable, not archived, not pending)
        if (! DB::table('status_labels')->where('name', 'Broken/Spare Parts')->exists()) {
            DB::table('status_labels')->insert([
                'name' => 'Broken/Spare Parts',
                'deployable' => 0,
                'pending' => 0,
                'archived' => 0,
                'notes' => 'Asset failed tests or used for spare parts.',
                'color' => '#d9534f', // red
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Ready for Sale: deployable type
        if (! DB::table('status_labels')->where('name', 'Ready for Sale')->exists()) {
            DB::table('status_labels')->insert([
                'name' => 'Ready for Sale',
                'deployable' => 1,
                'pending' => 0,
                'archived' => 0,
                'notes' => 'Refurbishing complete; item is sellable.',
                'color' => '#5cb85c', // green
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Sold: archived type
        if (! DB::table('status_labels')->where('name', 'Sold')->exists()) {
            DB::table('status_labels')->insert([
                'name' => 'Sold',
                'deployable' => 0,
                'pending' => 0,
                'archived' => 1,
                'notes' => 'Asset has been sold.',
                'color' => '#777777', // gray
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('status_labels')
            ->whereIn('name', [
                'Being Refurbished',
                'Broken/Spare Parts',
                'Ready for Sale',
                'Sold',
            ])->delete();
    }
};
