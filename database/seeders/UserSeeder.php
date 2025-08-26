<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use App\Models\TestAudit;
use App\Models\TestRun;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
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
        TestAudit::truncate();
        TestRun::truncate();
        User::truncate();

        if (! Company::count()) {
            $this->call(CompanySeeder::class);
        }

        $companyIds = Company::all()->pluck('id');

        if (! Department::count()) {
            $this->call(DepartmentSeeder::class);
        }

        $departmentIds = Department::all()->pluck('id');

        User::factory()->count(1)->firstAdmin()
            ->state(new Sequence(fn($sequence) => [
                'company_id' => $companyIds->random(),
                'department_id' => $departmentIds->random(),
            ]))
            ->create();

        User::factory()->count(1)->snipeAdmin()
            ->state(new Sequence(fn($sequence) => [
                'company_id' => $companyIds->random(),
                'department_id' => $departmentIds->random(),
            ]))
            ->create();

        User::factory()->count(1)->testAdmin()
            ->state(new Sequence(fn($sequence) => [
                'company_id' => $companyIds->random(),
                'department_id' => $departmentIds->random(),
            ]))
            ->create();

        User::factory()->count(3)->superuser()
            ->state(new Sequence(fn($sequence) => [
                'company_id' => $companyIds->random(),
                'department_id' => $departmentIds->random(),
            ]))
            ->create();

        User::factory()->count(3)->admin()
            ->state(new Sequence(fn($sequence) => [
                'company_id' => $companyIds->random(),
                'department_id' => $departmentIds->random(),
            ]))
            ->create();

        User::factory()->count(50)->viewAssets()
            ->state(new Sequence(fn($sequence) => [
                'company_id' => $companyIds->random(),
                'department_id' => $departmentIds->random(),
            ]))
            ->create();

        // Demo users for showcasing different permission levels
        User::factory()->superuser()
            ->state(new Sequence(fn($sequence) => [
                'company_id' => $companyIds->random(),
                'department_id' => $departmentIds->random(),
            ]))
            ->create([
                'username' => 'demo_super',
                'email' => 'demo_super@example.com',
                'first_name' => 'Demo',
                'last_name' => 'Super',
            ]);

        User::factory()->admin()
            ->state(new Sequence(fn($sequence) => [
                'company_id' => $companyIds->random(),
                'department_id' => $departmentIds->random(),
            ]))
            ->create([
                'username' => 'demo_admin',
                'email' => 'demo_admin@example.com',
                'first_name' => 'Demo',
                'last_name' => 'Admin',
            ]);

        User::factory()->viewAssets()
            ->state(new Sequence(fn($sequence) => [
                'company_id' => $companyIds->random(),
                'department_id' => $departmentIds->random(),
            ]))
            ->create([
                'username' => 'demo_user',
                'email' => 'demo_user@example.com',
                'first_name' => 'Demo',
                'last_name' => 'User',
            ]);

        User::factory()
            ->state(new Sequence(fn($sequence) => [
                'company_id' => $companyIds->random(),
                'department_id' => $departmentIds->random(),
            ]))
            ->create([
                'username'    => 'demo_refurbisher',
                'email'       => 'demo_refurbisher@example.com',
                'first_name'  => 'Demo',
                'last_name'   => 'Refurbisher',
                'permissions' => json_encode([
                    'refurbisher' => 1,
                    'scanning'    => 1,
                ]),
            ]);

        User::factory()
            ->state(new Sequence(fn($sequence) => [
                'company_id' => $companyIds->random(),
                'department_id' => $departmentIds->random(),
            ]))
            ->create([
                'username'    => 'demo_senior_refurbisher',
                'email'       => 'demo_senior_refurbisher@example.com',
                'first_name'  => 'Demo',
                'last_name'   => 'Senior',
                'permissions' => json_encode([
                    'senior-refurbisher' => 1,
                    'scanning'           => 1,
                    'tests.execute'      => 1,
                ]),
            ]);

        User::factory()
            ->state(new Sequence(fn($sequence) => [
                'company_id' => $companyIds->random(),
                'department_id' => $departmentIds->random(),
            ]))
            ->create([
                'username'    => 'demo_supervisor',
                'email'       => 'demo_supervisor@example.com',
                'first_name'  => 'Demo',
                'last_name'   => 'Supervisor',
                'permissions' => json_encode([
                    'supervisor'    => 1,
                    'scanning'      => 1,
                    'tests.execute' => 1,
                    'assets.create' => 1,
                    'assets.delete' => 1,
                    'tests.delete'  => 1,
                ]),
            ]);

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

        $users = User::orderBy('id', 'asc')->take(20)->get();
        $file_number = 1;

        foreach ($users as $user) {

            $user->avatar = $file_number.'.jpg';
            $user->save();
            $file_number++;
        }
        


    }
}
