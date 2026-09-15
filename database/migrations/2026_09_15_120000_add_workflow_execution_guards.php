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
        if (Schema::hasTable('workflow_profiles') && !Schema::hasColumn('workflow_profiles', 'repeat_policy')) {
            Schema::table('workflow_profiles', function (Blueprint $table): void {
                $table->string('repeat_policy')
                    ->default(WorkflowProfile::REPEAT_OVERRIDE_REQUIRED)
                    ->after('display_order');
            });
        }

        if (!Schema::hasTable('workflow_profile_dependencies')) {
            Schema::create('workflow_profile_dependencies', function (Blueprint $table): void {
                $table->unsignedInteger('workflow_profile_id');
                $table->unsignedInteger('prerequisite_workflow_profile_id');
                $table->timestamps();

                $table->primary(
                    ['workflow_profile_id', 'prerequisite_workflow_profile_id'],
                    'workflow_profile_dependency_pk'
                );
                $table->index(
                    'prerequisite_workflow_profile_id',
                    'workflow_profile_dependency_prerequisite_idx'
                );
                $table->foreign('workflow_profile_id', 'workflow_profile_dependency_profile_fk')
                    ->references('id')
                    ->on('workflow_profiles')
                    ->restrictOnDelete();
                $table->foreign(
                    'prerequisite_workflow_profile_id',
                    'workflow_profile_dependency_prerequisite_fk'
                )
                    ->references('id')
                    ->on('workflow_profiles')
                    ->restrictOnDelete();
            });
        }

        if (Schema::hasTable('workflow_runs')) {
            Schema::table('workflow_runs', function (Blueprint $table): void {
                if (!Schema::hasColumn('workflow_runs', 'prerequisite_snapshot')) {
                    $table->json('prerequisite_snapshot')->nullable()->after('readiness_context_hash');
                }
                if (!Schema::hasColumn('workflow_runs', 'profile_display_order_snapshot')) {
                    $table->integer('profile_display_order_snapshot')->nullable()->after('profile_slug_snapshot');
                }
                if (!Schema::hasColumn('workflow_runs', 'guard_override_by')) {
                    $table->unsignedInteger('guard_override_by')->nullable()->after('user_id');
                }
                if (!Schema::hasColumn('workflow_runs', 'guard_override_reason')) {
                    $table->text('guard_override_reason')->nullable()->after('guard_override_by');
                }
                if (!Schema::hasColumn('workflow_runs', 'guard_override_at')) {
                    $table->timestamp('guard_override_at')->nullable()->after('guard_override_reason');
                }
                if (!Schema::hasColumn('workflow_runs', 'guard_override_details')) {
                    $table->json('guard_override_details')->nullable()->after('guard_override_at');
                }
            });

            if (Schema::hasColumn('workflow_runs', 'guard_override_by')) {
                Schema::table('workflow_runs', function (Blueprint $table): void {
                    $table->foreign('guard_override_by', 'workflow_run_guard_override_user_fk')
                        ->references('id')
                        ->on('users')
                        ->nullOnDelete();
                });
            }

            if (Schema::hasColumn('workflow_runs', 'profile_display_order_snapshot')) {
                $profileOrders = DB::table('workflow_profiles')->pluck('display_order', 'id');

                foreach ($profileOrders as $profileId => $displayOrder) {
                    DB::table('workflow_runs')
                        ->where('workflow_profile_id', $profileId)
                        ->whereNull('profile_display_order_snapshot')
                        ->update(['profile_display_order_snapshot' => $displayOrder]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('workflow_runs')) {
            Schema::table('workflow_runs', function (Blueprint $table): void {
                if (Schema::hasColumn('workflow_runs', 'guard_override_by')) {
                    if (DB::getDriverName() === 'sqlite') {
                        $table->dropForeign(['guard_override_by']);
                    } else {
                        $table->dropForeign('workflow_run_guard_override_user_fk');
                    }
                }
            });

            Schema::table('workflow_runs', function (Blueprint $table): void {
                $columns = collect([
                    'prerequisite_snapshot',
                    'profile_display_order_snapshot',
                    'guard_override_by',
                    'guard_override_reason',
                    'guard_override_at',
                    'guard_override_details',
                ])->filter(fn (string $column): bool => Schema::hasColumn('workflow_runs', $column))->all();

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        Schema::dropIfExists('workflow_profile_dependencies');

        if (Schema::hasTable('workflow_profiles') && Schema::hasColumn('workflow_profiles', 'repeat_policy')) {
            Schema::table('workflow_profiles', function (Blueprint $table): void {
                $table->dropColumn('repeat_policy');
            });
        }
    }
};
