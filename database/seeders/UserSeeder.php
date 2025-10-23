<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use App\Models\TestAudit;
use App\Models\TestRun;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        TestAudit::truncate();
        TestRun::truncate();
        User::truncate();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Companies are not seeded; users remain unscoped
        if (! Department::count()) {
            $this->call(DepartmentSeeder::class);
        }

        $departments = Department::query()
            ->pluck('id', 'name');

        $users = [];

        $users[] = User::factory()
            ->superuser()
            ->create([
                'username' => 'admin',
                'email' => 'admin@example.com',
                'first_name' => 'Avery',
                'last_name' => 'Benton',
                'department_id' => $departments->get('Refurb Operations'),
                'company_id' => null,
                'permissions' => json_encode(['superuser' => 1]),
            ]);

        $createdBy = $users[0]->id;

        $crew = [
            [
                'attributes' => [
                    'username' => 'qa_manager',
                    'email' => 'qa.manager@example.com',
                    'first_name' => 'Quinn',
                    'last_name' => 'Adler',
                    'department_id' => $departments->get('Quality Assurance'),
                    'company_id' => null,
                    'created_by' => $createdBy,
                    'permissions' => json_encode([
                        'tests.execute' => 1,
                        'scanning' => 1,
                        'audits.view' => 1,
                    ]),
                ],
            ],
            [
                'attributes' => [
                    'username' => 'bench_tech',
                    'email' => 'bench.tech@example.com',
                    'first_name' => 'Riley',
                    'last_name' => 'Patel',
                    'department_id' => $departments->get('Refurb Operations'),
                    'company_id' => null,
                    'created_by' => $createdBy,
                    'permissions' => json_encode([
                        'refurbisher' => 1,
                        'scanning' => 1,
                    ]),
                ],
            ],
            [
                'attributes' => [
                    'username' => 'inventory_clerk',
                    'email' => 'inventory.clerk@example.com',
                    'first_name' => 'Morgan',
                    'last_name' => 'Lee',
                    'department_id' => $departments->get('Inventory Control'),
                    'company_id' => null,
                    'created_by' => $createdBy,
                    'permissions' => json_encode([
                        'assets.create' => 1,
                        'assets.edit' => 1,
                        'scanning' => 1,
                    ]),
                ],
            ],
            [
                'attributes' => [
                    'username' => 'support_viewer',
                    'email' => 'support.viewer@example.com',
                    'first_name' => 'Jordan',
                    'last_name' => 'Casey',
                    'department_id' => $departments->get('Quality Assurance'),
                    'company_id' => null,
                    'created_by' => $createdBy,
                    'permissions' => json_encode([
                        'assets.view' => 1,
                    ]),
                ],
            ],
        ];

        foreach ($crew as $seed) {
            $factory = User::factory();

            if (isset($seed['state']) && is_callable($seed['state'])) {
                $factory = $seed['state']($factory);
            }

            $users[] = $factory->create($seed['attributes']);
        }

        $src = public_path('/img/demo/avatars/');
        $dst = 'avatars'.'/';
        $del_files = Storage::files($dst);

        foreach ($del_files as $del_file) { // iterate files
            $file_to_delete = str_replace($src, '', $del_file);
            Log::debug('Deleting: '.$file_to_delete);
            try {
                Storage::disk('public')->delete($dst.$del_file);
            } catch (\Exception $e) {
                Log::debug($e);
            }
        }

        $add_files = glob($src.'/*.*');
        foreach ($add_files as $add_file) {
            $file_to_copy = str_replace($src, '', $add_file);
            Log::debug('Copying: '.$file_to_copy);
            try {
                Storage::disk('public')->put($dst.$file_to_copy, file_get_contents($src.$file_to_copy));
            } catch (\Exception $e) {
                Log::debug($e);
            }
        }

        $fileNumber = 1;
        foreach ($users as $user) {
            $user->avatar = $fileNumber.'.jpg';
            $user->save();
            $fileNumber++;
        }
        


    }
}
