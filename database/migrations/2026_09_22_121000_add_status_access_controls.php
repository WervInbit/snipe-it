<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('status_labels', function (Blueprint $table): void {
            $table->boolean('requires_note')->default(false)->after('show_in_nav');
        });

        Schema::create('status_label_access_rules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('status_label_id');
            $table->string('subject_type', 16);
            $table->unsignedInteger('subject_id');
            $table->tinyInteger('view_value')->default(0);
            $table->tinyInteger('select_value')->default(0);
            $table->timestamps();

            $table->unique(
                ['status_label_id', 'subject_type', 'subject_id'],
                'status_access_subject_unique'
            );
            $table->index(['subject_type', 'subject_id']);
            $table->foreign('status_label_id')->references('id')->on('status_labels')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_label_access_rules');

        Schema::table('status_labels', function (Blueprint $table): void {
            $table->dropColumn('requires_note');
        });
    }
};
