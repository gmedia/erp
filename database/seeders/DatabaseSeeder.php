<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Employment;
use App\Models\Permission;
use App\Models\Position;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
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
        $testUser = User::updateOrCreate(
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

        // Test User Employee (for test@example.com — ensures CheckPermission middleware finds employee)
        $testEmployee = Employee::updateOrCreate([
            'email' => 'test@example.com',
            'user_id' => $testUser->id,
        ], [
            'employee_id' => 'EMP-TEST01',
            'name' => 'Test User',
            'phone' => '1234567890',
        ]);

        Employment::factory()->withDepartment($departmentId)->withPosition($positionId)->withBranch($branchId)->for($testEmployee)->create([
            'company_id' => 1,
            'salary' => 50000,
            'hire_date' => now(),
            'employment_status' => 'regular',
        ]);

        // Admin Employee
        $adminEmployee = Employee::updateOrCreate([
            'email' => config('app.admin'),
            'user_id' => $admin->id,
        ], [
            'employee_id' => 'EMP-00000',
            'name' => 'Admin User',
            'phone' => '1234567890',
        ]);

        Employment::factory()->withDepartment($departmentId)->withPosition($positionId)->withBranch($branchId)->for($adminEmployee)->create([
            'company_id' => 1,
            'salary' => 100000,
            'hire_date' => now(),
            'employment_status' => 'regular',
        ]);

        // Sample Employees for Approvals
        $hrManagerUser = User::where('email', 'manager.hr@dokfin.id')->first();
        $hrManagerEmployee = Employee::updateOrCreate([
            'email' => 'manager.hr@dokfin.id',
            'user_id' => $hrManagerUser->id,
        ], [
            'employee_id' => 'EMP-HR001',
            'name' => 'HR Manager',
            'phone' => '081234567891',
        ]);

        Employment::factory()->withDepartment(Department::where('name', 'HR')->value('id'))->withPosition(Position::where('name', 'Manager')->value('id'))->withBranch($branchId)->for($hrManagerEmployee)->create([
            'company_id' => 1,
            'salary' => 15000000,
            'hire_date' => now()->subYears(2),
            'employment_status' => 'regular',
        ]);

        $financeDirectorUser = User::where('email', 'director.finance@dokfin.id')->first();
        $financeDirectorEmployee = Employee::updateOrCreate([
            'email' => 'director.finance@dokfin.id',
            'user_id' => $financeDirectorUser->id,
        ], [
            'employee_id' => 'EMP-FIN001',
            'name' => 'Finance Director',
            'phone' => '081234567892',
        ]);

        Employment::factory()->withDepartment(Department::where('name', 'Finance')->value('id'))->withPosition(Position::where('name', 'Director')->value('id'))->withBranch($branchId)->for($financeDirectorEmployee)->create([
            'company_id' => 1,
            'salary' => 25000000,
            'hire_date' => now()->subYears(5),
            'employment_status' => 'regular',
        ]);

        $itStaffUser = User::where('email', 'staff.it@dokfin.id')->first();
        $itStaffEmployee = Employee::updateOrCreate([
            'email' => 'staff.it@dokfin.id',
            'user_id' => $itStaffUser->id,
        ], [
            'employee_id' => 'EMP-IT001',
            'name' => 'IT Staff',
            'phone' => '081234567893',
        ]);

        Employment::factory()->withDepartment(Department::where('name', 'Engineering')->value('id'))->withPosition(Position::where('name', 'Senior')->value('id'))->withBranch($branchId)->for($itStaffEmployee)->create([
            'company_id' => 1,
            'salary' => 12000000,
            'hire_date' => now()->subYears(1),
            'employment_status' => 'regular',
        ]);

        $this->call([
            PermissionSeeder::class,
            MenuSeeder::class,
            SettingSampleDataSeeder::class,
            PipelineSampleDataSeeder::class,
            ApprovalFlowSampleDataSeeder::class,
            ApprovalDelegationSampleDataSeeder::class,
            ProductSampleDataSeeder::class,
            PurchasingSampleDataSeeder::class,
            ApSampleDataSeeder::class,
            AssetSampleDataSeeder::class,
            StockTransferSampleDataSeeder::class,
            InventoryStocktakeSampleDataSeeder::class,
            StockAdjustmentSampleDataSeeder::class,
            StockMovementSampleDataSeeder::class,
            ArSampleDataSeeder::class,
            GlExtendedSampleDataSeeder::class,
            ReportConfigurationSeeder::class,
            BudgetSampleDataSeeder::class,
        ]);

        // Assign all permissions to sample users for easy testing
        $allPermissions = Permission::all();
        foreach ([
            'test@example.com',
            config('app.admin'),
            'manager.hr@dokfin.id',
            'director.finance@dokfin.id',
            'staff.it@dokfin.id',
        ] as $email) {
            $employee = Employee::where('email', $email)->first();
            if ($employee) {
                $employee->permissions()->sync($allPermissions);
            }
        }
    }
}
