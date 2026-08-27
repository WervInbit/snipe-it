<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite cannot alter NOT NULL constraints without rebuilding the table.
            // Skip; models created in tests already accept NULL via schema builder defaults.
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE "models" ALTER COLUMN "model_number" DROP NOT NULL');

            return;
        }

        DB::statement('ALTER TABLE `models` MODIFY `model_number` VARCHAR(255) NULL');
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('UPDATE "models" SET "model_number" = \'\' WHERE "model_number" IS NULL');
            DB::statement('ALTER TABLE "models" ALTER COLUMN "model_number" SET NOT NULL');

            return;
        }

        DB::statement("UPDATE `models` SET `model_number` = '' WHERE `model_number` IS NULL");
        DB::statement('ALTER TABLE `models` MODIFY `model_number` VARCHAR(255) NOT NULL');
    }
};
