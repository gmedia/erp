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

        // Seed sample users for approvals
        User::updateOrCreate(
            ['email' => 'manager.hr@dokfin.id'],
            [
                'name' => 'HR Manager',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'director.finance@dokfin.id'],
            [
                'name' => 'Finance Director',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'staff.it@dokfin.id'],
            [
                'name' => 'IT Staff',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->call([
            BranchSeeder::class,
            WarehouseSeeder::class,
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

        // Admin Employee
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

        // Sample Employees for Approvals
        $hrManagerUser = User::where('email', 'manager.hr@dokfin.id')->first();
        Employee::updateOrCreate([
            'email' => 'manager.hr@dokfin.id',
            'user_id' => $hrManagerUser->id,
        ], [
            'employee_id' => 'EMP-HR001',
            'name' => 'HR Manager',
            'phone' => '081234567891',
            'department_id' => Department::where('name', 'HR')->value('id'),
            'position_id' => Position::where('name', 'Manager')->value('id'),
            'branch_id' => $branchId,
            'salary' => 15000000,
            'hire_date' => now()->subYears(2),
            'employment_status' => 'regular',
        ]);

        $financeDirectorUser = User::where('email', 'director.finance@dokfin.id')->first();
        Employee::updateOrCreate([
            'email' => 'director.finance@dokfin.id',
            'user_id' => $financeDirectorUser->id,
        ], [
            'employee_id' => 'EMP-FIN001',
            'name' => 'Finance Director',
            'phone' => '081234567892',
            'department_id' => Department::where('name', 'Finance')->value('id'),
            'position_id' => Position::where('name', 'Director')->value('id'),
            'branch_id' => $branchId,
            'salary' => 25000000,
            'hire_date' => now()->subYears(5),
            'employment_status' => 'regular',
        ]);

        $itStaffUser = User::where('email', 'staff.it@dokfin.id')->first();
        Employee::updateOrCreate([
            'email' => 'staff.it@dokfin.id',
            'user_id' => $itStaffUser->id,
        ], [
            'employee_id' => 'EMP-IT001',
            'name' => 'IT Staff',
            'phone' => '081234567893',
            'department_id' => Department::where('name', 'Engineering')->value('id'),
            'position_id' => Position::where('name', 'Senior')->value('id'),
            'branch_id' => $branchId,
            'salary' => 12000000,
            'hire_date' => now()->subYears(1),
            'employment_status' => 'regular',
        ]);

        $this->call([
            PermissionSeeder::class,
            MenuSeeder::class,
            SettingSampleSeeder::class,
            PipelineSampleSeeder::class,
            ApprovalFlowSampleSeeder::class,
            ApprovalDelegationSampleSeeder::class,
            ProductSampleDataSeeder::class,
            AssetSampleDataSeeder::class,
            StockTransferSampleDataSeeder::class,
            InventoryStocktakeSampleDataSeeder::class,
            StockAdjustmentSampleDataSeeder::class,
        ]);

        // Assign all permissions to sample users for easy testing
        $allPermissions = \App\Models\Permission::all();
        foreach ([config('app.admin'), 'manager.hr@dokfin.id', 'director.finance@dokfin.id', 'staff.it@dokfin.id'] as $email) {
            $employee = Employee::where('email', $email)->first();
            if ($employee) {
                $employee->permissions()->sync($allPermissions);
            }
        }
    }
}
