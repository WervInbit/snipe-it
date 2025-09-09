<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('asset_images')) {
            return;
        }

        $assets = DB::table('assets')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->get(['id', 'image', 'name']);

        foreach ($assets as $asset) {
            // Check if an entry already exists to avoid duplicates
            $exists = DB::table('asset_images')
                ->where('asset_id', $asset->id)
                ->where('file_path', 'assets/'.$asset->image)
                ->exists();

            if (! $exists) {
                DB::table('asset_images')->insert([
                    'asset_id' => $asset->id,
                    'file_path' => 'assets/'.$asset->image,
                    'caption' => $asset->name ?: 'Primary image',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('asset_images')) {
            return;
        }

        DB::table('asset_images')->whereIn('file_path', function ($query) {
            $query->select(DB::raw("CONCAT('assets/', image)"))
                ->from('assets')
                ->whereNotNull('image')
                ->where('image', '!=', '');
        })->delete();
    }
};
