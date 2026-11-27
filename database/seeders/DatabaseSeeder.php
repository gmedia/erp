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
        // Karyawan demo: kode berurutan EMP-000001.. tanpa celah. Admin User adalah akun
        // admin (CheckPermission membutuhkan baris employee), bergaji wajar.
        $demoEmployees = [
            [
                'code' => 'EMP-000001', 'name' => 'Rina Kartika', 'email' => 'rina.kartika@example.com',
                'phone' => '081234567800', 'department' => DemoMasterData::DEPARTMENT_OPERATIONS,
                'position' => DemoMasterData::POSITION_MANAGER,
                'salary' => 15000000, 'years' => 3, 'user' => $testUser,
            ],
            [
                'code' => 'EMP-000002', 'name' => 'Admin User', 'email' => config('app.admin'),
                'phone' => '081234567801', 'department' => DemoMasterData::DEPARTMENT_ENGINEERING,
                'position' => DemoMasterData::POSITION_LEAD,
                'salary' => 12000000, 'years' => 4, 'user' => $admin,
            ],
            [
                'code' => 'EMP-000003', 'name' => 'Hendra Wijaya', 'email' => 'hendra.wijaya@example.com',
                'phone' => '081234567891', 'department' => DemoMasterData::DEPARTMENT_HR,
                'position' => DemoMasterData::POSITION_MANAGER,
                'salary' => 15000000, 'years' => 2,
                'user' => User::where('email', 'manager.hr@example.com')->first(),
            ],
            [
                'code' => 'EMP-000004', 'name' => 'Dewi Anggraini', 'email' => 'dewi.anggraini@example.com',
                'phone' => '081234567892', 'department' => DemoMasterData::DEPARTMENT_FINANCE,
                'position' => DemoMasterData::POSITION_DIRECTOR,
                'salary' => 25000000, 'years' => 5,
                'user' => User::where('email', 'director.finance@example.com')->first(),
            ],
            [
                'code' => 'EMP-000005', 'name' => 'Agus Prasetyo', 'email' => 'agus.prasetyo@example.com',
                'phone' => '081234567893', 'department' => DemoMasterData::DEPARTMENT_ENGINEERING,
                'position' => DemoMasterData::POSITION_SENIOR_DEVELOPER,
                'salary' => 12000000, 'years' => 1,
                'user' => User::where('email', 'staff.it@example.com')->first(),
            ],
        ];

        foreach ($demoEmployees as $person) {
            // Kunci: user_id (stabil). Email demo bisa berubah antar versi seed;
            // mencari dengan email ikut berubah dan menimbulkan baris ganda.
            $employee = Employee::updateOrCreate([
                'user_id' => $person['user']->id,
            ], [
                'email' => $person['email'],
                'employee_id' => $person['code'],
                'name' => $person['name'],
                'phone' => $person['phone'],
            ]);

            Employment::updateOrCreate(
                ['employee_id' => $employee->id, 'is_current' => true],
                [
                    'company_id' => $companyId,
                    'department_id' => Department::where('name', $person['department'])->value('id'),
                    'position_id' => Position::where('name', $person['position'])->value('id'),
                    'branch_id' => $branchId,
                    'salary' => $person['salary'],
                    'hire_date' => now()->subYears($person['years']),
                    'employment_status' => 'regular',
                ],
            );
        }

        // Batas id jurnal sebelum data demo: jurnal dengan id <= batas dibuat pengguna
        // (atau run seeder sebelumnya) dan nomornya tidak boleh berubah (#503 butir 3).
        $journalIdBoundary = (int) DB::table('journal_entries')->max('id');

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
            ApMonthlyDemoSeeder::class,
            PurchasingMonthlyDemoSeeder::class,
            AssetSampleDataSeeder::class,
            VehicleLoanSampleDataSeeder::class,
            StockTransferSampleDataSeeder::class,
            InventoryStocktakeSampleDataSeeder::class,
            StockAdjustmentSampleDataSeeder::class,
            StockMovementSampleDataSeeder::class,
            ArSampleDataSeeder::class,
            ArMonthlyDemoSeeder::class,
            // Normalisasi depresiasi aset (semua aset, termasuk kendaraan, sudah
            // ada) sebelum rekonsiliasi GL (W7-05). Harus sebelum GlExtendedSampleDataSeeder,
            // karena periode bulan yang diposting di sini ditutup di seeder tersebut (#423).
            AssetDepreciationNormalizationSeeder::class,
            GlExtendedSampleDataSeeder::class,
            ReportConfigurationSeeder::class,
            BudgetSampleDataSeeder::class,
        ]);

        $this->spreadIdenticalAuditTimestamps();
        $this->renumberDemoJournalEntries($journalIdBoundary);

        // Penutupan tahun lalu paling akhir: jurnal penutup dibentuk setelah semua
        // transaksi demo tercatat, karena penutupan annual mengunci seluruh FY (#571).
        $this->call(DemoPeriodClosingSeeder::class);

        // Assign all permissions to sample users for easy testing. Cari karyawan lewat
        // user_id: email karyawan bisa berbeda dari email user di data demo lama (PR432).
        $allPermissions = Permission::all();
        foreach ([
            'rina.kartika@example.com',
            config('app.admin'),
            'manager.hr@example.com',
            'director.finance@example.com',
            'staff.it@example.com',
        ] as $email) {
            $user = User::where('email', $email)->first();
            $employee = $user
                ? Employee::where('user_id', $user->id)->first()
                : null;
            $employee ??= Employee::where('email', $email)->first();

            if ($employee) {
                $employee->permissions()->sync($allPermissions);
            }
        }
    }

    /**
     * Nomor jurnal demo dibentuk saat posting, sehingga urutannya mengikuti urutan pembuatan,
     * bukan tanggal jurnal. Nomor JV diurutkan ulang per tahun fiskal menurut tanggal jurnal
     * (lalu id) agar naik sesuai tanggal (#503 butir 3). Dua pass karena entry_number unik.
     *
     * Nomor jurnal yang sudah diposting pengguna tidak boleh berubah: bila ada jurnal JV dengan
     * created_by (dibuat pengguna) yang sudah ada sebelum run ini, renumber dilewati seluruhnya.
     * Jurnal sistem/demo (created_by null) tidak dikunci.
     */
    private function renumberDemoJournalEntries(int $journalIdBoundary): void
    {
        $hasUserJournal = DB::table('journal_entries')
            ->where('id', '<=', $journalIdBoundary)
            ->where('entry_number', 'like', 'JV-%')
            ->whereNotNull('created_by')
            ->exists();

        if ($hasUserJournal) {
            return;
        }

        $entries = DB::table('journal_entries')
            ->where('entry_number', 'like', 'JV-%')
            ->orderBy('fiscal_year_id')
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get(['id', 'fiscal_year_id', 'entry_number']);

        // Pass 1: nomor sementara agar tidak bentrok dengan unique constraint.
        foreach ($entries as $entry) {
            DB::table('journal_entries')->where('id', $entry->id)->update(['entry_number' => "TMP-{$entry->id}"]);
        }

        // Pass 2: nomor final per tahun fiskal, berurutan sesuai tanggal.
        $sequenceByYear = [];
        foreach ($entries as $entry) {
            $year = substr($entry->entry_number, 3, 4);
            $key = $entry->fiscal_year_id . '|' . $year;
            $sequenceByYear[$key] = ($sequenceByYear[$key] ?? 0) + 1;

            DB::table('journal_entries')
                ->where('id', $entry->id)
                ->update(['entry_number' => sprintf('JV-%s-%05d', $year, $sequenceByYear[$key])]);
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
