<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('asset_status_events')) {
            return;
        }

        if (! Schema::hasColumn('asset_status_events', 'legacy_history_id')) {
            Schema::table('asset_status_events', function (Blueprint $table): void {
                $table->unsignedBigInteger('legacy_history_id')->nullable();
            });
        }

        if (! Schema::hasTable('asset_status_history')) {
            return;
        }

        DB::table('asset_status_history')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('asset_status_events')->updateOrInsert(
                        ['legacy_history_id' => $row->id],
                        [
                            'asset_id' => $row->asset_id,
                            'from_status_id' => $row->old_status_id,
                            'to_status_id' => $row->new_status_id,
                            'triggered_by' => $row->changed_by,
                            'note' => null,
                            'created_at' => $row->changed_at,
                            'updated_at' => $row->changed_at,
                        ],
                    );
                }
            }, 'id');
    }

    public function down(): void
    {
        if (! Schema::hasTable('asset_status_events')
            || ! Schema::hasColumn('asset_status_events', 'legacy_history_id')) {
            return;
        }

        DB::table('asset_status_events')
            ->whereNotNull('legacy_history_id')
            ->delete();

        Schema::table('asset_status_events', function (Blueprint $table): void {
            $table->dropColumn('legacy_history_id');
        });
    }
};
