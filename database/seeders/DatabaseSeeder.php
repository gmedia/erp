<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
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

        // Seed default demo user
        $legacyDemoUser = User::where('email', 'test@example.com')->first();

        if ($legacyDemoUser && ! User::where('email', 'rina.kartika@example.com')->exists()) {
            $legacyDemoUser->update([
                'email' => 'rina.kartika@example.com',
                'name' => 'Rina Kartika',
            ]);
        }

        $testUser = User::updateOrCreate(
            ['email' => 'rina.kartika@example.com'],
            [
                'name' => 'Rina Kartika',
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
            ['email' => 'manager.hr@example.com'],
            [
                'name' => 'HR Manager',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'director.finance@example.com'],
            [
                'name' => 'Finance Director',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'staff.it@example.com'],
            [
                'name' => 'IT Staff',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->call([
            AuthenticationSettingSeeder::class,
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
        $companyId = Company::query()->value('id')
            ?? Company::query()->firstOrCreate(['name' => 'PT. Default Company'])->id;

        $legacyDemoEmployee = Employee::where('email', 'test@example.com')->first();

        if ($legacyDemoEmployee && ! Employee::where('email', 'rina.kartika@example.com')->exists()) {
            $legacyDemoEmployee->update([
                'email' => 'rina.kartika@example.com',
                'name' => 'Rina Kartika',
                'employee_id' => 'EMP-2022-0031',
            ]);
        }

        // Demo user employee (ensures CheckPermission middleware finds employee)
        $testEmployee = Employee::updateOrCreate([
            'email' => 'rina.kartika@example.com',
            'user_id' => $testUser->id,
        ], [
            'employee_id' => 'EMP-2022-0031',
            'name' => 'Rina Kartika',
            'phone' => '1234567890',
        ]);

        Employment::factory()->withDepartment($departmentId)->withPosition($positionId)->withBranch($branchId)->for($testEmployee)->create([
            'company_id' => $companyId,
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
            'company_id' => $companyId,
            'salary' => 100000,
            'hire_date' => now(),
            'employment_status' => 'regular',
        ]);

        // Sample Employees for Approvals
        $hrManagerUser = User::where('email', 'manager.hr@example.com')->first();
        $hrManagerEmployee = Employee::updateOrCreate([
            'email' => 'manager.hr@example.com',
            'user_id' => $hrManagerUser->id,
        ], [
            'employee_id' => 'EMP-HR001',
            'name' => 'HR Manager',
            'phone' => '081234567891',
        ]);

        Employment::factory()->withDepartment(Department::where('name', 'HR')->value('id'))->withPosition(Position::where('name', 'Manager')->value('id'))->withBranch($branchId)->for($hrManagerEmployee)->create([
            'company_id' => $companyId,
            'salary' => 15000000,
            'hire_date' => now()->subYears(2),
            'employment_status' => 'regular',
        ]);

        $financeDirectorUser = User::where('email', 'director.finance@example.com')->first();
        $financeDirectorEmployee = Employee::updateOrCreate([
            'email' => 'director.finance@example.com',
            'user_id' => $financeDirectorUser->id,
        ], [
            'employee_id' => 'EMP-FIN001',
            'name' => 'Finance Director',
            'phone' => '081234567892',
        ]);

        Employment::factory()->withDepartment(Department::where('name', 'Finance')->value('id'))->withPosition(Position::where('name', 'Director')->value('id'))->withBranch($branchId)->for($financeDirectorEmployee)->create([
            'company_id' => $companyId,
            'salary' => 25000000,
            'hire_date' => now()->subYears(5),
            'employment_status' => 'regular',
        ]);

        $itStaffUser = User::where('email', 'staff.it@example.com')->first();
        $itStaffEmployee = Employee::updateOrCreate([
            'email' => 'staff.it@example.com',
            'user_id' => $itStaffUser->id,
        ], [
            'employee_id' => 'EMP-IT001',
            'name' => 'IT Staff',
            'phone' => '081234567893',
        ]);

        Employment::factory()->withDepartment(Department::where('name', 'Engineering')->value('id'))->withPosition(Position::where('name', 'Senior')->value('id'))->withBranch($branchId)->for($itStaffEmployee)->create([
            'company_id' => $companyId,
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
            VehicleLoanApprovalFlowSeeder::class,
            ApprovalDelegationSampleDataSeeder::class,
            ProductSampleDataSeeder::class,
            PurchasingSampleDataSeeder::class,
            ApSampleDataSeeder::class,
            AssetSampleDataSeeder::class,
            VehicleLoanSampleDataSeeder::class,
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
            'rina.kartika@example.com',
            config('app.admin'),
            'manager.hr@example.com',
            'director.finance@example.com',
            'staff.it@example.com',
        ] as $email) {
            $employee = Employee::where('email', $email)->first();
            if ($employee) {
                $employee->permissions()->sync($allPermissions);
            }
        }
    }
}
