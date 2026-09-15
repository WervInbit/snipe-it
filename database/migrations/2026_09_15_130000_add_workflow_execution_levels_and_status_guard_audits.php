<?php

use App\Models\WorkflowProfile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('workflow_profiles') && !Schema::hasColumn('workflow_profiles', 'execution_level')) {
            Schema::table('workflow_profiles', function (Blueprint $table): void {
                $table->string('execution_level')
                    ->default(WorkflowProfile::EXECUTION_OPERATOR)
                    ->after('repeat_policy');
            });
        }

        if (Schema::hasTable('workflow_profiles') && Schema::hasColumn('workflow_profiles', 'repeat_policy')) {
            DB::table('workflow_profiles')
                ->where('repeat_policy', 'allowed')
                ->update(['repeat_policy' => WorkflowProfile::REPEAT_OVERRIDE_REQUIRED]);
        }

        if (Schema::hasTable('asset_status_events')) {
            Schema::table('asset_status_events', function (Blueprint $table): void {
                if (!Schema::hasColumn('asset_status_events', 'guard_confirmation_hash')) {
                    $table->string('guard_confirmation_hash', 64)->nullable()->after('note');
                }
                if (!Schema::hasColumn('asset_status_events', 'guard_override_reason')) {
                    $table->text('guard_override_reason')->nullable()->after('guard_confirmation_hash');
                }
                if (!Schema::hasColumn('asset_status_events', 'guard_override_details')) {
                    $table->json('guard_override_details')->nullable()->after('guard_override_reason');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('asset_status_events')) {
            Schema::table('asset_status_events', function (Blueprint $table): void {
                $columns = collect([
                    'guard_confirmation_hash',
                    'guard_override_reason',
                    'guard_override_details',
                ])->filter(fn (string $column): bool => Schema::hasColumn('asset_status_events', $column))->all();

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('workflow_profiles') && Schema::hasColumn('workflow_profiles', 'execution_level')) {
            Schema::table('workflow_profiles', function (Blueprint $table): void {
                $table->dropColumn('execution_level');
            });
        }
    }
};
