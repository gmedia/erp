<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Seed default test user
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Seed admin user for Playwright test
        $admin = User::updateOrCreate(
            ['email' => config('app.admin')],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->call([
            BranchSeeder::class,
            DepartmentSeeder::class,
            PositionSeeder::class,
            CustomerCategorySeeder::class,
            SupplierCategorySeeder::class,
            ProductCategorySeeder::class,
            UnitSeeder::class,
            CoaSeeder::class,
            AssetCategorySeeder::class,
        ]);

        $departmentId = Department::query()->value('id');
        $positionId = Position::query()->value('id');
        $branchId = Branch::query()->value('id');

        Employee::updateOrCreate([
            'email' => config('app.admin'),
            'user_id' => $admin->id,
        ], [
            'employee_id' => 'EMP-00000',
            'name' => 'Admin User',
            'phone' => '1234567890',
            'department_id' => $departmentId,
            'position_id' => $positionId,
            'branch_id' => $branchId,
            'salary' => 100000,
            'hire_date' => now(),
            'employment_status' => 'regular',
        ]);

        $this->call([
            PermissionSeeder::class,
            MenuSeeder::class,
            SettingSeeder::class,
            PipelineSeeder::class,
            ProductSampleDataSeeder::class,
            AssetSampleDataSeeder::class,
        ]);
    }
}
