<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\TestType;
use App\Models\Category;

/**
 * Seed the test_types table while respecting foreign keys.
 */
class TestTypeSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * When test_types is referenced by test_results via a foreign key,
         * TRUNCATE will fail with SQLSTATE[42000] 1701.  The pattern below
         * deletes the dependent rows, deletes the parents, resets the
         * auto-increment counters, and then reseeds.
         */

        // Temporarily disable FK checks on this connection
        Schema::disableForeignKeyConstraints();

        // Delete rows in child table(s) referencing test_types
        DB::table('test_results')->delete();
        DB::table('category_test_type')->delete();

        // Delete all test_type rows instead of truncate
        TestType::query()->delete();

        // Reset the auto-increment counters on both tables
        DB::statement('ALTER TABLE test_results AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE test_types AUTO_INCREMENT = 1');

        // Re-enable FK checks
        Schema::enableForeignKeyConstraints();

        $categoryIds = Category::query()
            ->where('category_type', 'asset')
            ->pluck('id')
            ->all();

        // Define the seed data
        $types = [
            ['name' => 'Cleaning - external', 'slug' => 'cleaning-external', 'tooltip' => 'Wipe down exterior surfaces'],
            ['name' => 'Cleaning - internal', 'slug' => 'cleaning-internal', 'tooltip' => 'Clean internal components and remove dust'],
        ];

        // Seed or update test types
        foreach ($types as $type) {
            $type['is_required'] = $type['is_required'] ?? true;

            $record = TestType::updateOrCreate(['slug' => $type['slug']], $type);

            if (!empty($categoryIds)) {
                $record->categories()->sync($categoryIds);
            }
        }
    }
}
