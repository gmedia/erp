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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
                'name' => 'Hendra Wijaya',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'director.finance@example.com'],
            [
                'name' => 'Dewi Anggraini',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'staff.it@example.com'],
            [
                'name' => 'Agus Prasetyo',
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
                'employee_id' => 'EMP-000001',
            ]);
        }

        // Demo user employee (ensures CheckPermission middleware finds employee)
        $testEmployee = Employee::updateOrCreate([
            'email' => 'rina.kartika@example.com',
            'user_id' => $testUser->id,
        ], [
            'employee_id' => 'EMP-000001',
            'name' => 'Rina Kartika',
            'phone' => '081234567800',
        ]);

        Employment::updateOrCreate(
            ['employee_id' => $testEmployee->id, 'is_current' => true],
            [
                'company_id' => $companyId,
                'department_id' => $departmentId,
                'position_id' => $positionId,
                'branch_id' => $branchId,
                'salary' => 50000,
                'hire_date' => now(),
                'employment_status' => 'regular',
            ],
        );

        // Admin Employee
        $adminEmployee = Employee::updateOrCreate([
            'email' => config('app.admin'),
            'user_id' => $admin->id,
        ], [
            'employee_id' => 'EMP-000002',
            'name' => 'Admin User',
            'phone' => '081234567801',
        ]);

        Employment::updateOrCreate(
            ['employee_id' => $adminEmployee->id, 'is_current' => true],
            [
                'company_id' => $companyId,
                'department_id' => $departmentId,
                'position_id' => $positionId,
                'branch_id' => $branchId,
                'salary' => 100000,
                'hire_date' => now(),
                'employment_status' => 'regular',
            ],
        );

        // Sample Employees for Approvals
        $hrManagerUser = User::where('email', 'manager.hr@example.com')->first();
        $hrManagerEmployee = Employee::updateOrCreate([
            'email' => 'manager.hr@example.com',
            'user_id' => $hrManagerUser->id,
        ], [
            'employee_id' => 'EMP-000003',
            'name' => 'Hendra Wijaya',
            'phone' => '081234567891',
        ]);

        Employment::updateOrCreate(
            ['employee_id' => $hrManagerEmployee->id, 'is_current' => true],
            [
                'company_id' => $companyId,
                'department_id' => Department::where('name', 'HR')->value('id'),
                'position_id' => Position::where('name', 'Manager')->value('id'),
                'branch_id' => $branchId,
                'salary' => 15000000,
                'hire_date' => now()->subYears(2),
                'employment_status' => 'regular',
            ],
        );

        $financeDirectorUser = User::where('email', 'director.finance@example.com')->first();
        $financeDirectorEmployee = Employee::updateOrCreate([
            'email' => 'director.finance@example.com',
            'user_id' => $financeDirectorUser->id,
        ], [
            'employee_id' => 'EMP-000004',
            'name' => 'Dewi Anggraini',
            'phone' => '081234567892',
        ]);

        Employment::updateOrCreate(
            ['employee_id' => $financeDirectorEmployee->id, 'is_current' => true],
            [
                'company_id' => $companyId,
                'department_id' => Department::where('name', 'Finance')->value('id'),
                'position_id' => Position::where('name', 'Director')->value('id'),
                'branch_id' => $branchId,
                'salary' => 25000000,
                'hire_date' => now()->subYears(5),
                'employment_status' => 'regular',
            ],
        );

        $itStaffUser = User::where('email', 'staff.it@example.com')->first();
        $itStaffEmployee = Employee::updateOrCreate([
            'email' => 'staff.it@example.com',
            'user_id' => $itStaffUser->id,
        ], [
            'employee_id' => 'EMP-000005',
            'name' => 'Agus Prasetyo',
            'phone' => '081234567893',
        ]);

        Employment::updateOrCreate(
            ['employee_id' => $itStaffEmployee->id, 'is_current' => true],
            [
                'company_id' => $companyId,
                'department_id' => Department::where('name', 'Engineering')->value('id'),
                'position_id' => Position::where('name', 'Senior')->value('id'),
                'branch_id' => $branchId,
                'salary' => 12000000,
                'hire_date' => now()->subYears(1),
                'employment_status' => 'regular',
            ],
        );

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
            // Normalisasi depresiasi aset (semua aset, termasuk kendaraan, sudah
            // ada) sebelum rekonsiliasi GL (W7-05). Harus sebelum GlExtendedSampleDataSeeder,
            // karena periode bulan yang diposting di sini ditutup di seeder tersebut (#423).
            AssetDepreciationNormalizationSeeder::class,
            GlExtendedSampleDataSeeder::class,
            ReportConfigurationSeeder::class,
            BudgetSampleDataSeeder::class,
        ]);

        $this->spreadIdenticalAuditTimestamps();

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

    /**
     * Seeder membuat banyak log pada detik yang sama; geser tiap duplikat
     * beberapa detik agar audit trail tidak tampak identik (W6-14).
     */
    private function spreadIdenticalAuditTimestamps(): void
    {
        foreach (['pipeline_state_logs', 'approval_audit_logs'] as $table) {
            $rows = DB::table($table)->orderBy('id')->get(['id', 'created_at']);
            $seen = [];

            foreach ($rows as $row) {
                $key = (string) $row->created_at;
                $seen[$key] = ($seen[$key] ?? 0) + 1;

                if ($seen[$key] === 1 || $row->created_at === null) {
                    continue;
                }

                DB::table($table)->where('id', $row->id)->update([
                    'created_at' => Carbon::parse($row->created_at)->addSeconds($seen[$key] - 1),
                ]);
            }
        }
    }
}
