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
        $supportsAlterForeignKeys = in_array($driver, ['mysql', 'mariadb'], true);

        if (!Schema::hasColumn('component_definitions', 'placement_mode')) {
            Schema::table('component_definitions', function (Blueprint $table): void {
                $table->string('placement_mode')->default('either')->after('serial_tracking_mode');
                $table->index('placement_mode', 'component_definitions_placement_mode_idx');
            });
        }

        Schema::table('component_instances', function (Blueprint $table): void {
            if (!Schema::hasColumn('component_instances', 'parent_component_instance_id')) {
                $table->unsignedBigInteger('parent_component_instance_id')->nullable()->after('current_asset_id');
                $table->index('parent_component_instance_id', 'component_instances_parent_idx');
            }

            if (!Schema::hasColumn('component_instances', 'root_asset_id')) {
                $table->unsignedInteger('root_asset_id')->nullable()->after('parent_component_instance_id');
                $table->index('root_asset_id', 'component_instances_root_asset_idx');
            }

            if (!Schema::hasColumn('component_instances', 'is_materialized_expected')) {
                $table->boolean('is_materialized_expected')->default(false)->after('root_asset_id');
                $table->index('is_materialized_expected', 'component_instances_materialized_idx');
            }

            if (!Schema::hasColumn('component_instances', 'materialized_reason')) {
                $table->string('materialized_reason')->nullable()->after('is_materialized_expected');
            }

            if (!Schema::hasColumn('component_instances', 'ancestry_parent_component_instance_id')) {
                $table->unsignedBigInteger('ancestry_parent_component_instance_id')->nullable()->after('materialized_reason');
            }

            if (!Schema::hasColumn('component_instances', 'ancestry_attached_through_at')) {
                $table->timestamp('ancestry_attached_through_at')->nullable()->after('ancestry_parent_component_instance_id');
            }

            if (!Schema::hasColumn('component_instances', 'ancestry_attached_through_event_id')) {
                $table->unsignedBigInteger('ancestry_attached_through_event_id')->nullable()->after('ancestry_attached_through_at');
            }
        });

        if ($supportsAlterForeignKeys) {
            $this->addForeignKeysIfMissing();
        }

        DB::table('component_instances')
            ->whereNotNull('current_asset_id')
            ->whereNull('root_asset_id')
            ->update(['root_asset_id' => DB::raw('current_asset_id')]);
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            Schema::table('component_instances', function (Blueprint $table): void {
                if (Schema::hasColumn('component_instances', 'parent_component_instance_id')) {
                    $table->dropForeign('component_instances_parent_fk');
                }

                if (Schema::hasColumn('component_instances', 'root_asset_id')) {
                    $table->dropForeign('component_instances_root_asset_fk');
                }

                if (Schema::hasColumn('component_instances', 'ancestry_parent_component_instance_id')) {
                    $table->dropForeign('component_instances_ancestry_parent_fk');
                }

                if (Schema::hasColumn('component_instances', 'ancestry_attached_through_event_id')) {
                    $table->dropForeign('component_instances_ancestry_event_fk');
                }
            });
        }

        Schema::table('component_instances', function (Blueprint $table): void {
            $columns = [
                'parent_component_instance_id',
                'root_asset_id',
                'is_materialized_expected',
                'materialized_reason',
                'ancestry_parent_component_instance_id',
                'ancestry_attached_through_at',
                'ancestry_attached_through_event_id',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('component_instances', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (Schema::hasColumn('component_definitions', 'placement_mode')) {
            Schema::table('component_definitions', function (Blueprint $table): void {
                $table->dropIndex('component_definitions_placement_mode_idx');
                $table->dropColumn('placement_mode');
            });
        }
    }

    private function addForeignKeysIfMissing(): void
    {
        Schema::table('component_instances', function (Blueprint $table): void {
            if (!$this->hasForeignKey('component_instances', 'component_instances_parent_fk')) {
                $table->foreign('parent_component_instance_id', 'component_instances_parent_fk')
                    ->references('id')
                    ->on('component_instances')
                    ->nullOnDelete();
            }

            if (!$this->hasForeignKey('component_instances', 'component_instances_root_asset_fk')) {
                $table->foreign('root_asset_id', 'component_instances_root_asset_fk')
                    ->references('id')
                    ->on('assets')
                    ->nullOnDelete();
            }

            if (!$this->hasForeignKey('component_instances', 'component_instances_ancestry_parent_fk')) {
                $table->foreign('ancestry_parent_component_instance_id', 'component_instances_ancestry_parent_fk')
                    ->references('id')
                    ->on('component_instances')
                    ->nullOnDelete();
            }

            if (!$this->hasForeignKey('component_instances', 'component_instances_ancestry_event_fk')) {
                $table->foreign('ancestry_attached_through_event_id', 'component_instances_ancestry_event_fk')
                    ->references('id')
                    ->on('component_events')
                    ->nullOnDelete();
            }
        });
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
};
