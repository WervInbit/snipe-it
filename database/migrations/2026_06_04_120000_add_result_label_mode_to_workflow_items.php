<?php

use App\Models\WorkflowProfileItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('workflow_items') && !Schema::hasColumn('workflow_items', 'result_label_mode')) {
            Schema::table('workflow_items', function (Blueprint $table): void {
                $table->string('result_label_mode')
                    ->default(WorkflowProfileItem::LABEL_MODE_PASS_FAIL)
                    ->after('is_required');
            });

            $itemModes = Schema::hasTable('workflow_profile_items')
                ? DB::table('workflow_profile_items')
                    ->select('workflow_item_id', 'result_label_mode')
                    ->where('result_label_mode', '<>', WorkflowProfileItem::LABEL_MODE_PASS_FAIL)
                    ->orderBy('id')
                    ->get()
                    ->unique('workflow_item_id')
                : collect();

            foreach ($itemModes as $itemMode) {
                DB::table('workflow_items')
                    ->where('id', $itemMode->workflow_item_id)
                    ->update(['result_label_mode' => $itemMode->result_label_mode]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('workflow_items') && Schema::hasColumn('workflow_items', 'result_label_mode')) {
            Schema::table('workflow_items', function (Blueprint $table): void {
                $table->dropColumn('result_label_mode');
            });
        }
    }
};
