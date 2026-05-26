<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('component_instances', function (Blueprint $table): void {
            if (!Schema::hasColumn('component_instances', 'lifecycle_status')) {
                $table->string('lifecycle_status')->default('in_stock')->after('status');
                $table->index('lifecycle_status', 'component_instances_lifecycle_status_idx');
            }

            if (!Schema::hasColumn('component_instances', 'condition_status')) {
                $table->string('condition_status')->default('needs_attention')->after('condition_code');
                $table->index('condition_status', 'component_instances_condition_status_idx');
            }
        });

        DB::table('component_instances')->update([
            'lifecycle_status' => DB::raw("
                case status
                    when 'installed' then 'attached'
                    when 'in_transfer' then 'in_tray'
                    when 'destruction_pending' then 'destruction_pending'
                    when 'destroyed_recycled' then 'destroyed'
                    when 'sold_returned' then 'sold_returned'
                    else 'in_stock'
                end
            "),
            'condition_status' => DB::raw("
                case
                    when status = 'needs_verification' then 'needs_attention'
                    when status = 'defective' then 'damaged'
                    when condition_code in ('poor', 'broken') then 'damaged'
                    when condition_code = 'unknown' then 'needs_attention'
                    else 'good'
                end
            "),
        ]);
    }

    public function down(): void
    {
        Schema::table('component_instances', function (Blueprint $table): void {
            if (Schema::hasColumn('component_instances', 'lifecycle_status')) {
                $table->dropIndex('component_instances_lifecycle_status_idx');
                $table->dropColumn('lifecycle_status');
            }

            if (Schema::hasColumn('component_instances', 'condition_status')) {
                $table->dropIndex('component_instances_condition_status_idx');
                $table->dropColumn('condition_status');
            }
        });
    }
};
