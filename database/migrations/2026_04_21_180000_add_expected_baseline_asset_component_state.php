<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (!Schema::hasTable('asset_expected_component_states')) {
            Schema::create('asset_expected_component_states', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('asset_id');
                $table->unsignedBigInteger('model_number_component_template_id');
                $table->unsignedInteger('removed_qty')->default(0);
                $table->timestamps();

                $table->unique(['asset_id', 'model_number_component_template_id'], 'asset_expected_component_state_unique');
                $table->foreign('asset_id', 'asset_expected_component_state_asset_fk')
                    ->references('id')
                    ->on('assets')
                    ->cascadeOnDelete();
                $table->foreign('model_number_component_template_id', 'asset_expected_component_state_template_fk')
                    ->references('id')
                    ->on('model_number_component_templates')
                    ->cascadeOnDelete();
            });
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('alter table asset_expected_component_states modify asset_id int unsigned not null');
            DB::statement('alter table asset_expected_component_states modify model_number_component_template_id bigint unsigned not null');

            if (!$this->hasIndex('asset_expected_component_states', 'asset_expected_component_state_unique')) {
                Schema::table('asset_expected_component_states', function (Blueprint $table) {
                    $table->unique(['asset_id', 'model_number_component_template_id'], 'asset_expected_component_state_unique');
                });
            }

            if (!$this->hasForeignKey('asset_expected_component_states', 'asset_expected_component_state_asset_fk')) {
                Schema::table('asset_expected_component_states', function (Blueprint $table) {
                    $table->foreign('asset_id', 'asset_expected_component_state_asset_fk')
                        ->references('id')
                        ->on('assets')
                        ->cascadeOnDelete();
                });
            }

            if (!$this->hasForeignKey('asset_expected_component_states', 'asset_expected_component_state_template_fk')) {
                Schema::table('asset_expected_component_states', function (Blueprint $table) {
                    $table->foreign('model_number_component_template_id', 'asset_expected_component_state_template_fk')
                        ->references('id')
                        ->on('model_number_component_templates')
                        ->cascadeOnDelete();
                });
            }
        }

        if (!Schema::hasColumn('component_definition_attributes', 'resolves_to_spec')) {
            Schema::table('component_definition_attributes', function (Blueprint $table) {
                $table->boolean('resolves_to_spec')->default(false)->after('attribute_option_id');
            });
        }

        DB::table('component_definition_attributes')
            ->whereIn('attribute_definition_id', function ($query) {
                $query->select('id')
                    ->from('attribute_definitions')
                    ->whereIn('datatype', ['int', 'decimal']);
            })
            ->update(['resolves_to_spec' => 1]);
    }

    public function down(): void
    {
        Schema::table('component_definition_attributes', function (Blueprint $table) {
            if (Schema::hasColumn('component_definition_attributes', 'resolves_to_spec')) {
                $table->dropColumn('resolves_to_spec');
            }
        });

        Schema::dropIfExists('asset_expected_component_states');
    }

    private function hasForeignKey(string $table, string $constraint): bool
    {
        $database = DB::getDatabaseName();

        $result = DB::selectOne(
            'select constraint_name from information_schema.table_constraints where constraint_schema = ? and table_name = ? and constraint_name = ? and constraint_type = ? limit 1',
            [$database, $table, $constraint, 'FOREIGN KEY']
        );

        return $result !== null;
    }

    private function hasIndex(string $table, string $index): bool
    {
        $database = DB::getDatabaseName();

        $result = DB::selectOne(
            'select index_name from information_schema.statistics where table_schema = ? and table_name = ? and index_name = ? limit 1',
            [$database, $table, $index]
        );

        return $result !== null;
    }
};
