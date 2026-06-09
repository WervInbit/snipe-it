<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('attribute_definitions', 'component_spec_display_mode')) {
            Schema::table('attribute_definitions', function (Blueprint $table): void {
                $table->string('component_spec_display_mode')
                    ->default('value_labels')
                    ->after('allow_asset_override');
            });
        }

        if (!Schema::hasColumn('component_definitions', 'spec_display_label')) {
            Schema::table('component_definitions', function (Blueprint $table): void {
                $table->string('spec_display_label')->nullable()->after('spec_summary');
            });
        }

        if (!Schema::hasColumn('component_definition_attributes', 'include_in_component_label')) {
            Schema::table('component_definition_attributes', function (Blueprint $table): void {
                $table->boolean('include_in_component_label')
                    ->default(false)
                    ->after('resolves_to_spec');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('component_definition_attributes', 'include_in_component_label')) {
            Schema::table('component_definition_attributes', function (Blueprint $table): void {
                $table->dropColumn('include_in_component_label');
            });
        }

        if (Schema::hasColumn('component_definitions', 'spec_display_label')) {
            Schema::table('component_definitions', function (Blueprint $table): void {
                $table->dropColumn('spec_display_label');
            });
        }

        if (Schema::hasColumn('attribute_definitions', 'component_spec_display_mode')) {
            Schema::table('attribute_definitions', function (Blueprint $table): void {
                $table->dropColumn('component_spec_display_mode');
            });
        }
    }
};
