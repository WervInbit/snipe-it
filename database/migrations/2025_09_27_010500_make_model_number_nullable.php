<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite cannot alter NOT NULL constraints without rebuilding the table.
            // Skip; models created in tests already accept NULL via schema builder defaults.
            return;
        }

        DB::statement('ALTER TABLE `models` MODIFY `model_number` VARCHAR(255) NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("UPDATE `models` SET `model_number` = '' WHERE `model_number` IS NULL");
        DB::statement('ALTER TABLE `models` MODIFY `model_number` VARCHAR(255) NOT NULL');
    }
};
