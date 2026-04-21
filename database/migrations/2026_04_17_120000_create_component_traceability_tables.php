<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('component_definitions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->unsignedInteger('category_id')->nullable();
            $table->unsignedInteger('manufacturer_id')->nullable();
            $table->unsignedInteger('company_id')->nullable();
            $table->string('model_number')->nullable();
            $table->string('part_code')->nullable();
            $table->text('spec_summary')->nullable();
            $table->json('metadata_json')->nullable();
            $table->string('serial_tracking_mode')->default('optional');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['category_id', 'is_active']);
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            $table->foreign('manufacturer_id')->references('id')->on('manufacturers')->nullOnDelete();
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('component_storage_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->unsignedInteger('site_location_id')->nullable();
            $table->string('type')->default('general');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['type', 'is_active']);
            $table->foreign('site_location_id')->references('id')->on('locations')->nullOnDelete();
        });

        Schema::create('component_instances', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('component_tag')->unique();
            $table->uuid('qr_uid')->unique();
            $table->foreignId('component_definition_id')->nullable()->constrained('component_definitions')->nullOnDelete();
            $table->unsignedInteger('company_id')->nullable();
            $table->string('display_name');
            $table->string('serial')->nullable();
            $table->string('status')->default('in_stock');
            $table->string('condition_code')->default('unknown');
            $table->string('source_type')->default('manual');
            $table->unsignedInteger('source_asset_id')->nullable();
            $table->unsignedInteger('current_asset_id')->nullable();
            $table->foreignId('storage_location_id')->nullable()->constrained('component_storage_locations')->nullOnDelete();
            $table->unsignedInteger('held_by_user_id')->nullable();
            $table->timestamp('transfer_started_at')->nullable();
            $table->timestamp('needs_verification_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->string('installed_as')->nullable();
            $table->unsignedInteger('supplier_id')->nullable();
            $table->decimal('purchase_cost', 20, 4)->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('destroyed_at')->nullable();
            $table->json('metadata_json')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('status');
            $table->index('current_asset_id');
            $table->index('source_asset_id');
            $table->index('held_by_user_id');
            $table->index('storage_location_id');
            $table->index(['status', 'held_by_user_id']);
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            $table->foreign('source_asset_id')->references('id')->on('assets')->nullOnDelete();
            $table->foreign('current_asset_id')->references('id')->on('assets')->nullOnDelete();
            $table->foreign('held_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('model_number_component_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_number_id')->constrained('model_numbers')->cascadeOnDelete();
            $table->foreignId('component_definition_id')->nullable()->constrained('component_definitions')->nullOnDelete();
            $table->string('expected_name');
            $table->string('slot_name')->nullable();
            $table->unsignedInteger('expected_qty')->default(1);
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata_json')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['model_number_id', 'sort_order'], 'mn_component_templates_model_sort_idx');
        });

        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('work_order_number')->unique();
            $table->unsignedInteger('company_id')->nullable();
            $table->unsignedInteger('primary_contact_user_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->string('priority')->nullable();
            $table->string('visibility_profile')->default('full');
            $table->json('portal_visibility_json')->nullable();
            $table->date('intake_date')->nullable();
            $table->date('due_date')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'status']);
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            $table->foreign('primary_contact_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('work_order_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->unsignedInteger('asset_id')->nullable();
            $table->string('customer_label')->nullable();
            $table->string('asset_tag_snapshot')->nullable();
            $table->string('serial_snapshot')->nullable();
            $table->string('qr_reference')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index('work_order_id');
            $table->foreign('asset_id')->references('id')->on('assets')->nullOnDelete();
        });

        Schema::create('work_order_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->foreignId('work_order_asset_id')->nullable()->constrained('work_order_assets')->nullOnDelete();
            $table->string('task_type')->default('general');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('pending');
            $table->boolean('customer_visible')->default(true);
            $table->string('customer_status_label')->nullable();
            $table->unsignedInteger('assigned_to')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('notes_internal')->nullable();
            $table->text('notes_customer')->nullable();
            $table->timestamps();
            $table->index(['work_order_id', 'status']);
            $table->index(['work_order_asset_id', 'status']);
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('work_order_user_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('granted_by')->nullable();
            $table->timestamps();
            $table->unique(['work_order_id', 'user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('granted_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('component_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('component_instance_id')->constrained('component_instances')->cascadeOnDelete();
            $table->string('event_type');
            $table->unsignedInteger('performed_by')->nullable();
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->unsignedInteger('from_asset_id')->nullable();
            $table->unsignedInteger('to_asset_id')->nullable();
            $table->foreignId('from_storage_location_id')->nullable()->constrained('component_storage_locations')->nullOnDelete();
            $table->foreignId('to_storage_location_id')->nullable()->constrained('component_storage_locations')->nullOnDelete();
            $table->unsignedInteger('held_by_user_id')->nullable();
            $table->foreignId('related_work_order_id')->nullable()->constrained('work_orders')->nullOnDelete();
            $table->foreignId('related_work_order_task_id')->nullable()->constrained('work_order_tasks')->nullOnDelete();
            $table->text('note')->nullable();
            $table->json('payload_json')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['component_instance_id', 'created_at']);
            $table->foreign('performed_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('from_asset_id')->references('id')->on('assets')->nullOnDelete();
            $table->foreign('to_asset_id')->references('id')->on('assets')->nullOnDelete();
            $table->foreign('held_by_user_id')->references('id')->on('users')->nullOnDelete();
        });

        $now = now();
        $defaults = collect(config('components.default_storage_locations', []))
            ->map(function (array $location) use ($now): array {
                return [
                    'name' => $location['name'],
                    'code' => $location['code'],
                    'type' => $location['type'],
                    'site_location_id' => null,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->all();

        if ($defaults !== []) {
            DB::table('component_storage_locations')->insert($defaults);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('component_events');
        Schema::dropIfExists('work_order_user_access');
        Schema::dropIfExists('work_order_tasks');
        Schema::dropIfExists('work_order_assets');
        Schema::dropIfExists('work_orders');
        Schema::dropIfExists('model_number_component_templates');
        Schema::dropIfExists('component_instances');
        Schema::dropIfExists('component_storage_locations');
        Schema::dropIfExists('component_definitions');
    }
};
