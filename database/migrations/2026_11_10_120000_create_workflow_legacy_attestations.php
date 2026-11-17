<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('workflow_legacy_attestations')) {
            Schema::create('workflow_legacy_attestations', function (Blueprint $table): void {
                $table->increments('id');
                $table->uuid('evidence_set_uuid');
                $table->unsignedInteger('asset_id');
                $table->unsignedBigInteger('model_number_id')->nullable();
                $table->unsignedInteger('workflow_profile_id')->nullable();
                $table->unsignedInteger('workflow_profile_item_id')->nullable();
                $table->unsignedInteger('workflow_item_id')->nullable();
                $table->string('profile_name_snapshot');
                $table->string('profile_slug_snapshot')->nullable();
                $table->string('item_name_snapshot');
                $table->string('item_slug_snapshot')->nullable();
                $table->string('requirement_context_hash', 64);
                $table->text('source_note');
                $table->date('source_tested_at')->nullable();
                $table->unsignedInteger('accepted_by')->nullable();
                $table->string('accepted_by_name_snapshot')->nullable();
                $table->timestamp('accepted_at');
                $table->timestamp('superseded_at')->nullable();
                $table->unsignedInteger('superseded_by_result_id')->nullable();
                $table->unsignedInteger('revoked_by')->nullable();
                $table->string('revoked_by_name_snapshot')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->text('revocation_reason')->nullable();
                $table->unsignedTinyInteger('active_slot')->nullable()->default(1);
                $table->timestamps();

                $table->foreign('asset_id', 'wf_legacy_asset_fk')
                    ->references('id')->on('assets')->cascadeOnDelete();
                $table->foreign('model_number_id', 'wf_legacy_model_number_fk')
                    ->references('id')->on('model_numbers')->nullOnDelete();
                $table->foreign('workflow_profile_id', 'wf_legacy_profile_fk')
                    ->references('id')->on('workflow_profiles')->nullOnDelete();
                $table->foreign('workflow_profile_item_id', 'wf_legacy_profile_item_fk')
                    ->references('id')->on('workflow_profile_items')->nullOnDelete();
                $table->foreign('workflow_item_id', 'wf_legacy_item_fk')
                    ->references('id')->on('workflow_items')->nullOnDelete();
                $table->foreign('accepted_by', 'wf_legacy_accepted_user_fk')
                    ->references('id')->on('users')->nullOnDelete();
                $table->foreign('superseded_by_result_id', 'wf_legacy_result_fk')
                    ->references('id')->on('workflow_results')->nullOnDelete();
                $table->foreign('revoked_by', 'wf_legacy_revoked_user_fk')
                    ->references('id')->on('users')->nullOnDelete();

                $table->unique(
                    ['asset_id', 'workflow_profile_item_id', 'active_slot'],
                    'wf_legacy_active_unique'
                );
                $table->index(['asset_id', 'revoked_at', 'superseded_at'], 'wf_legacy_asset_state_idx');
                $table->index('evidence_set_uuid', 'wf_legacy_set_idx');
            });
        }

        if (Schema::hasTable('workflow_runs') && !Schema::hasColumn('workflow_runs', 'legacy_attestation_snapshot')) {
            Schema::table('workflow_runs', function (Blueprint $table): void {
                $table->json('legacy_attestation_snapshot')->nullable()->after('prerequisite_snapshot');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('workflow_legacy_attestations')
            && DB::table('workflow_legacy_attestations')->exists()) {
            throw new RuntimeException(
                'Legacy workflow attestation rollback refused because audit evidence exists.'
            );
        }

        if (Schema::hasTable('workflow_runs')
            && Schema::hasColumn('workflow_runs', 'legacy_attestation_snapshot')
            && DB::table('workflow_runs')->whereNotNull('legacy_attestation_snapshot')->exists()) {
            throw new RuntimeException(
                'Legacy workflow attestation rollback refused because workflow snapshots exist.'
            );
        }

        if (Schema::hasTable('workflow_runs') && Schema::hasColumn('workflow_runs', 'legacy_attestation_snapshot')) {
            Schema::table('workflow_runs', function (Blueprint $table): void {
                $table->dropColumn('legacy_attestation_snapshot');
            });
        }

        Schema::dropIfExists('workflow_legacy_attestations');
    }
};
