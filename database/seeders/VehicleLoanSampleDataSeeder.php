<?php

namespace Database\Seeders;

use App\Actions\AccountingPosting\PostAssetAcquisitionJournalAction;
use App\Actions\AccountingPosting\ResolveControlAccountAction;
use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStep;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Employment;
use App\Models\JournalEntryLine;
use App\Models\Position;
use App\Models\Setting;
use App\Models\User;
use App\Models\VehicleDriver;
use App\Models\VehicleLoan;
use App\Models\VehicleLoanActivityLog;
use App\Models\VehicleLoanPickup;
use App\Models\VehicleLoanReturn;
use App\Models\VehicleUnit;
use Database\Seeders\Concerns\ReopensDemoPeriodClosings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Data contoh realistis untuk seluruh tahapan modul Vehicle Loans.
 *
 * Setiap status diwakili satu record: draft, pending, in_progress, approved,
 * rejected, pickup_done, returned, cancelled, ditambah satu pickup_done yang
 * overdue. ApprovalRequest dibuat sebagai source of truth, lengkap dengan
 * step-nya, agar badge/approval queue konsisten dengan status mirror di
 * vehicle_loans. Nilai yang tampil di UI memakai data wajar (bukan kata
 * "sample"/"dummy") agar demo terasa seperti data operasional nyata.
 */
class VehicleLoanSampleDataSeeder extends Seeder
{
    use ReopensDemoPeriodClosings;

    protected function seedDemoData(): void
    {
        $flow = ApprovalFlow::where('code', config('vehicle_loan.approval_flow_code'))->first();
        $flowStep = $flow
            ? ApprovalFlowStep::where('approval_flow_id', $flow->id)->orderBy('step_order')->first()
            : null;

        $admin = User::where('email', config('app.admin'))->first()
            ?? User::query()->orderBy('id')->first();
        if (! $admin) {
            return;
        }

        $this->purgeLegacySampleData();

        $requesters = $this->ensureRequesters();
        $drivers = $this->ensureDrivers($admin);
        $units = $this->ensureUnits();

        if ($units === [] || $drivers === []) {
            // Master data tidak bisa dibuat aman tanpa Employee/Asset kategori
            // kendaraan. Seeder lain wajib berjalan lebih dulu.
            return;
        }

        $branchId = Branch::query()->orderBy('id')->value('id');
        $companyId = Company::query()->orderBy('id')->value('id');
        $departmentId = Department::query()->orderBy('id')->value('id');

        $scenarios = [
            ['code' => 'VL-2026-0001', 'status' => 'draft', 'unit' => 0, 'purpose' => 'Perjalanan dinas ke Kantor Cabang Bandung'],
            ['code' => 'VL-2026-0002', 'status' => 'pending', 'unit' => 1, 'purpose' => 'Antar dokumen kontrak ke kantor notaris'],
            ['code' => 'VL-2026-0003', 'status' => 'in_progress', 'unit' => 2, 'purpose' => 'Kunjungan koordinasi proyek di Gedung Menara Kuningan'],
            ['code' => 'VL-2026-0004', 'status' => 'approved', 'unit' => 0, 'purpose' => 'Penjemputan tamu dari Bandara Soekarno-Hatta'],
            ['code' => 'VL-2026-0005', 'status' => 'rejected', 'unit' => 1, 'purpose' => 'Survey lokasi gudang di Cikarang'],
            ['code' => 'VL-2026-0006', 'status' => 'pickup_done', 'unit' => 0, 'purpose' => 'Pengiriman sparepart ke vendor di Tangerang', 'ready' => true],
            ['code' => 'VL-2026-0007', 'status' => 'returned', 'unit' => 1, 'purpose' => 'Rapat koordinasi bulanan di kantor pusat'],
            ['code' => 'VL-2026-0008', 'status' => 'cancelled', 'unit' => 2, 'purpose' => 'Kunjungan supplier ke pabrik Karawang'],
            ['code' => 'VL-2026-0009', 'status' => 'pickup_done', 'unit' => 2, 'purpose' => 'Perjalanan dinas ke Surabaya', 'overdue' => true],
        ];

        foreach ($scenarios as $index => $scenario) {
            $driver = $drivers[$index % count($drivers)];
            $unit = $units[(int) ($scenario['unit'] ?? $index % count($units)) % count($units)];

            $loan = $this->seedLoan($scenario, $index, $requesters, $driver, $unit, [
                'branch_id' => $branchId,
                'company_id' => $companyId,
                'department_id' => $departmentId,
            ]);

            $this->seedApproval($loan, $scenario['status'], $flow, $flowStep, $admin, $requesters, $driver, $unit);
            $this->seedPickupAndReturn($loan, $scenario['status'], $requesters, $admin, $driver, $unit, (bool) ($scenario['overdue'] ?? false), (bool) ($scenario['ready'] ?? false));
            $this->seedActivityLog($loan, $scenario['status'], $admin);
        }
    }

    /**
     * @param  array<string, mixed>  $scenario
     * @param  list<int>  $requesters
     * @param  array{branch_id: mixed, company_id: mixed, department_id: mixed}  $org
     */
    private function seedLoan(array $scenario, int $index, array $requesters, VehicleDriver $driver, VehicleUnit $unit, array $org): VehicleLoan
    {
        $status = $scenario['status'];
        $overdue = (bool) ($scenario['overdue'] ?? false);

        if ($overdue) {
            $start = now()->subDays(3)->setTime(8, 0);
            $end = now()->subDay()->setTime(17, 0);
        } elseif ($status === 'returned') {
            $start = now()->subDays(6)->setTime(8, 0);
            $end = now()->subDays(5)->setTime(17, 0);
        } elseif ($status === 'pickup_done') {
            $start = now()->subDay()->setTime(8, 0);
            $end = now()->setTime(17, 0);
        } else {
            $start = now()->addDays($index + 1)->setTime(8, 0);
            $end = (clone $start)->setTime(17, 0);
        }

        if (in_array($status, ['draft'], true)) {
            $submittedAt = null;
        } else {
            $candidateSubmitted = (clone $start)->subDay()->setTime(9, 0);
            $submittedAt = $candidateSubmitted->greaterThan(now())
                ? now()->subHours(2)
                : $candidateSubmitted;
        }

        $approvedAt = null;
        if ($submittedAt && in_array($status, ['approved', 'pickup_done', 'returned'], true)) {
            $candidateApproved = (clone $submittedAt)->addHours(2);
            $approvedAt = $candidateApproved->greaterThan(now())
                ? now()->subMinutes(30)
                : $candidateApproved;
        }

        $rejectionReason = $status === 'rejected'
            ? 'Jadwal bentrok dengan kebutuhan unit lain pada jam yang sama.'
            : null;

        $reviewNotes = in_array($status, ['in_progress', 'approved'], true)
            ? 'Driver dan unit disesuaikan setelah pengecekan jadwal operasional.'
            : null;

        return VehicleLoan::updateOrCreate(
            ['code' => $scenario['code']],
            [
                'requester_id' => $requesters[$index % count($requesters)],
                'branch_id' => $org['branch_id'],
                'company_id' => $org['company_id'],
                'department_id' => $org['department_id'],
                'start_at' => $start,
                'end_at' => $end,
                'purpose' => $scenario['purpose'],
                'driver_id' => $driver->id,
                'vehicle_unit_id' => $unit->id,
                'status' => $status,
                'is_overdue' => $overdue,
                'submitted_at' => $submittedAt,
                'approved_at' => $approvedAt,
                'approved_by' => $approvedAt ? $this->approverId() : null,
                'rejection_reason' => $rejectionReason,
                'review_notes' => $reviewNotes,
            ],
        );
    }

    private function seedApproval(
        VehicleLoan $loan,
        string $status,
        ?ApprovalFlow $flow,
        ?ApprovalFlowStep $flowStep,
        User $admin,
        array $requesters,
        VehicleDriver $driver,
        VehicleUnit $unit,
    ): void {
        if (! $flow || ! $flowStep || in_array($status, ['draft', 'cancelled'], true)) {
            return;
        }

        $approvalStatus = match ($status) {
            'pending', 'in_progress' => 'pending',
            'rejected' => 'rejected',
            default => 'approved',
        };

        $approval = ApprovalRequest::updateOrCreate(
            [
                'approvable_type' => VehicleLoan::class,
                'approvable_id' => $loan->id,
            ],
            [
                'approval_flow_id' => $flow->id,
                'current_step_order' => 1,
                'status' => $approvalStatus,
                'submitted_by' => $loan->requester_id,
                'submitted_at' => $loan->submitted_at ?? now(),
                'completed_at' => in_array($approvalStatus, ['approved', 'rejected'], true)
                    ? ($loan->approved_at ?? now())
                    : null,
            ],
        );

        $stepStatus = match ($status) {
            'pending' => 'pending',
            'in_progress' => 'approved',
            'rejected' => 'rejected',
            default => 'approved',
        };

        ApprovalRequestStep::updateOrCreate(
            [
                'approval_request_id' => $approval->id,
                'approval_flow_step_id' => $flowStep->id,
            ],
            [
                'step_order' => $flowStep->step_order,
                'status' => $stepStatus,
                'acted_by' => $stepStatus === 'pending' ? null : $this->approverId(),
                'action' => $stepStatus === 'pending' ? null : ($stepStatus === 'rejected' ? 'reject' : 'approve'),
                'comments' => $status === 'rejected'
                    ? 'Jadwal bentrok dengan kebutuhan unit lain pada jam yang sama.'
                    : ($status === 'in_progress' ? 'Driver dan unit diperbarui oleh atasan.' : null),
                'acted_at' => $stepStatus === 'pending' ? null : ($loan->approved_at ?? now()),
            ],
        );

        if ($status === 'in_progress') {
            $loan->update([
                'review_notes' => 'Penugasan driver dan unit diperbarui oleh atasan.',
            ]);
        }
    }

    /** @param list<int> $requesters */
    private function seedPickupAndReturn(
        VehicleLoan $loan,
        string $status,
        array $requesters,
        User $admin,
        VehicleDriver $driver,
        VehicleUnit $unit,
        bool $overdue,
        bool $ready,
    ): void {
        if (! in_array($status, ['pickup_done', 'returned'], true)) {
            return;
        }

        $pickedAt = $overdue
            ? now()->subDays(3)->setTime(8, 0)
            : (clone $loan->start_at);

        $pickup = VehicleLoanPickup::updateOrCreate(
            ['vehicle_loan_id' => $loan->id],
            [
                'requester_id' => $requesters[0] ?? $admin->id,
                'photo_mode' => 'with_driver',
                'selfie_photo_path' => 'vehicle-loans/pickups/' . $loan->code . '.jpg',
                'selfie_photo_name' => 'selfie.jpg',
                'driver_snapshot_id' => $driver->id,
                'unit_snapshot_id' => $unit->id,
                'picked_at' => $pickedAt,
                'key_handover_at' => $ready ? null : (clone $pickedAt)->addMinutes(15),
                'security_user_id' => $ready ? null : $admin->id,
            ],
        );

        if ($status !== 'returned') {
            return;
        }

        $returnedAt = (clone $loan->end_at)->setTime(16, 30);

        VehicleLoanReturn::updateOrCreate(
            ['pickup_id' => $pickup->id],
            [
                'vehicle_loan_id' => $loan->id,
                'returner_id' => $requesters[0] ?? $admin->id,
                'handover_photo_path' => 'vehicle-loans/returns/' . $loan->code . '-handover.jpg',
                'handover_photo_name' => 'handover.jpg',
                'condition_photo_path' => 'vehicle-loans/returns/' . $loan->code . '-condition.jpg',
                'condition_photo_name' => 'condition.jpg',
                'incident' => false,
                'odometer_start' => 12500,
                'odometer_end' => 12680,
                'returned_at' => $returnedAt,
                'received_by_security_id' => $admin->id,
            ],
        );
    }

    private function seedActivityLog(VehicleLoan $loan, string $status, User $admin): void
    {
        $events = match ($status) {
            'draft' => ['draft_created'],
            'pending' => ['submitted'],
            'in_progress' => ['submitted', 'assignment_changed', 'approval_step_advanced'],
            'approved' => ['submitted', 'approval_completed'],
            'rejected' => ['submitted', 'approval_rejected'],
            'pickup_done' => ['submitted', 'approval_completed', 'pickup_submitted', 'key_handed_over'],
            'returned' => ['submitted', 'approval_completed', 'pickup_submitted', 'key_handed_over', 'return_submitted'],
            'cancelled' => ['draft_created', 'cancelled'],
            default => [],
        };

        $times = [
            'draft_created' => $loan->created_at,
            'submitted' => $loan->submitted_at,
            'approval_step_advanced' => $loan->updated_at,
            'approval_completed' => $loan->approved_at,
            'approval_rejected' => $loan->updated_at,
            'assignment_changed' => $loan->approved_at,
            'cancelled' => $loan->created_at,
            'pickup_submitted' => $loan->pickup?->picked_at,
            'key_handed_over' => $loan->pickup?->key_handover_at,
            'return_submitted' => $loan->returns?->returned_at,
        ];

        foreach ($events as $event) {
            if ($event === 'key_handed_over' && $loan->pickup?->key_handover_at === null) {
                continue;
            }

            VehicleLoanActivityLog::firstOrCreate(
                [
                    'vehicle_loan_id' => $loan->id,
                    'event' => $event,
                ],
                [
                    'actor_user_id' => $admin->id,
                    'created_at' => $times[$event] ?? $loan->created_at,
                ],
            );
        }
    }

    private function purgeLegacySampleData(): void
    {
        $loanIds = VehicleLoan::where('code', 'like', 'VL-SAMPLE-%')->pluck('id');

        if ($loanIds->isNotEmpty()) {
            VehicleLoanReturn::whereIn('vehicle_loan_id', $loanIds)->delete();
            VehicleLoanPickup::whereIn('vehicle_loan_id', $loanIds)->delete();

            $approvalIds = ApprovalRequest::where('approvable_type', VehicleLoan::class)
                ->whereIn('approvable_id', $loanIds)
                ->pluck('id');

            ApprovalRequestStep::whereIn('approval_request_id', $approvalIds)->delete();
            ApprovalRequest::whereIn('id', $approvalIds)->delete();
            VehicleLoanActivityLog::whereIn('vehicle_loan_id', $loanIds)->delete();

            VehicleLoan::whereIn('id', $loanIds)->forceDelete();
        }

        $legacyDriver = VehicleDriver::where('license_number', 'SIM-SAMPLE-001')->first();
        if ($legacyDriver && ! VehicleLoan::where('driver_id', $legacyDriver->id)->exists()) {
            $legacyDriver->delete();
        }

        $legacyUnit = VehicleUnit::where('plate_number', 'B-SAMPLE-1')->first();
        if ($legacyUnit) {
            $legacyAsset = Asset::find($legacyUnit->asset_id);

            if (! VehicleLoan::where('vehicle_unit_id', $legacyUnit->id)->exists()) {
                $legacyUnit->delete();
            }

            if ($legacyAsset && ! VehicleUnit::where('asset_id', $legacyAsset->id)->exists()) {
                $legacyAsset->delete();
            }
        }

        $legacyEmployee = Employee::where('employee_id', 'EMP-SAMPLE-VL')->first();
        if ($legacyEmployee && ! VehicleDriver::where('employee_id', $legacyEmployee->id)->exists()) {
            $legacyEmployee->delete();
        }
    }

    private function approverId(): int
    {
        $email = Setting::get('vehicle_loan_approver_email');
        if (is_string($email) && $email !== '') {
            $id = User::where('email', $email)->value('id');
            if ($id) {
                return (int) $id;
            }
        }

        $admin = User::where('email', config('app.admin'))->value('id');

        return (int) ($admin ?? User::query()->orderBy('id')->value('id'));
    }

    /**
     * @return list<int>
     */
    private function ensureRequesters(): array
    {
        $people = [
            ['email' => 'dewi.lestari@example.com', 'name' => 'Dewi Lestari'],
            ['email' => 'andi.pratama@example.com', 'name' => 'Andi Pratama'],
            ['email' => 'siti.rahmawati@example.com', 'name' => 'Siti Rahmawati'],
        ];

        $ids = [];

        foreach ($people as $person) {
            $user = User::firstOrCreate(
                ['email' => $person['email']],
                [
                    'name' => $person['name'],
                    'password' => Hash::make(Str::random(32)),
                    'email_verified_at' => now(),
                ],
            );

            if ($user->name !== $person['name']) {
                $user->update(['name' => $person['name']]);
            }

            $ids[] = $user->id;
        }

        return $ids;
    }

    /**
     * @return list<VehicleDriver>
     */
    private function ensureDrivers(User $admin): array
    {
        $people = [
            ['employee_id' => 'EMP-000006', 'name' => 'Budi Santoso', 'email' => 'budi.santoso@example.com', 'phone' => '081234567811', 'license' => 'SIM-B-2024-00123', 'type' => 'B'],
            ['employee_id' => 'EMP-000007', 'name' => 'Agus Setiawan', 'email' => 'agus.setiawan@example.com', 'phone' => '081234567812', 'license' => 'SIM-B-2022-01170', 'type' => 'B'],
            ['employee_id' => 'EMP-000008', 'name' => 'Joko Susilo', 'email' => 'joko.susilo@example.com', 'phone' => '081234567813', 'license' => 'SIM-A-2020-00881', 'type' => 'A'],
        ];

        $companyId = Company::query()->value('id');
        $branchId = Branch::query()->orderBy('id')->value('id');
        // Driver kendaraan adalah staf operasional, bukan HR/Finance.
        $departmentId = Department::query()->where('name', 'Operations')->value('id');
        $positionId = Position::query()->where('name', 'Junior')->value('id');

        $drivers = [];

        foreach ($people as $person) {
            // `employee_id` dan `email` sama-sama unik. Cocokkan keduanya agar
            // baris lama (mis. dari format kode sebelumnya) tidak memicu
            // unique violation saat seeder dijalankan ulang.
            $employee = Employee::query()
                ->where('employee_id', $person['employee_id'])
                ->orWhere('email', $person['email'])
                ->first()
                ?? new Employee;

            $employee->fill([
                'employee_id' => $person['employee_id'],
                'name' => $person['name'],
                'email' => $person['email'],
                'phone' => $person['phone'],
                'user_id' => $admin->id,
            ])->save();

            if ($companyId !== null) {
                Employment::updateOrCreate(
                    ['employee_id' => $employee->id, 'is_current' => true],
                    [
                        'company_id' => $companyId,
                        'department_id' => $departmentId,
                        'position_id' => $positionId,
                        'branch_id' => $branchId,
                        'salary' => 8500000,
                        'hire_date' => now()->subYears(3),
                        'employment_status' => 'regular',
                    ],
                );
            }

            // `vehicle_drivers.employee_id` unik; cocokkan juga lewat
            // license_number agar data lama tidak menghasilkan duplikat.
            $driver = VehicleDriver::query()
                ->where('employee_id', $employee->id)
                ->orWhere('license_number', $person['license'])
                ->first()
                ?? new VehicleDriver;

            $driver->fill([
                'employee_id' => $employee->id,
                'license_number' => $person['license'],
                'license_type' => $person['type'],
                'license_expiry' => now()->addYears(5)->toDateString(),
                'is_active' => true,
            ])->save();

            $drivers[] = $driver;
        }

        return $drivers;
    }

    /**
     * @return list<VehicleUnit>
     */
    private function ensureUnits(): array
    {
        $category = AssetCategory::where('code', 'KND')->first()
            ?? AssetCategory::query()->orderBy('id')->first();
        $branchId = Branch::query()->orderBy('id')->value('id');

        if (! $category || ! $branchId) {
            return [];
        }

        $vehicles = [
            ['asset_code' => 'FA-000014', 'name' => 'Toyota Avanza 1.5 G', 'plate' => 'B 1234 XYZ', 'type' => 'car', 'capacity' => 5],
            ['asset_code' => 'FA-000015', 'name' => 'Honda Vario 160 CBS', 'plate' => 'B 5678 ABC', 'type' => 'motorcycle', 'capacity' => 2],
            ['asset_code' => 'FA-000013', 'name' => 'Mitsubishi L300 Pickup', 'plate' => 'B 9012 DEF', 'type' => 'car', 'capacity' => 3],
        ];

        // Kendaraan operasional berada di department Operations (W6-16).
        $operationsDepartment = Department::query()->where('name', 'Operations')->first();

        $units = [];

        foreach ($vehicles as $vehicle) {
            $asset = Asset::updateOrCreate(
                ['asset_code' => $vehicle['asset_code']],
                [
                    'name' => $vehicle['name'],
                    'asset_category_id' => $category->id,
                    'branch_id' => $branchId,
                    'department_id' => $operationsDepartment?->id,
                    'purchase_date' => now()->subYears(3)->toDateString(),
                    'purchase_cost' => 150000000,
                    'currency' => 'IDR',
                    'status' => 'active',
                    'condition' => 'good',
                    'depreciation_method' => 'straight_line',
                    'depreciation_start_date' => now()->subYears(3)->toDateString(),
                    'useful_life_months' => 60,
                    'salvage_value' => 0,
                ],
            );

            // Kendaraan dibuat setelah AssetSampleDataSeeder, jadi perolehannya
            // harus dijurnal di sini lewat action (#357).
            app(PostAssetAcquisitionJournalAction::class)->execute($asset);

            // Register awal = saldo opening dari jurnal ACQ (bukan selisih GL).
            // Kolom book_value default 0 akan membuat run depresiasi melewati
            // kendaraan ini (#357).
            $accumulatedAccountId = app(ResolveControlAccountAction::class)
                ->execute('12200', $asset->purchase_date->toDateString())
                ->id;
            $openingAccumulated = (float) JournalEntryLine::query()
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                ->where('journal_entries.reference', 'ACQ-' . $asset->asset_code)
                ->where('journal_entries.status', 'posted')
                ->where('journal_entry_lines.account_id', $accumulatedAccountId)
                ->sum('journal_entry_lines.credit');

            $asset->forceFill([
                'accumulated_depreciation' => $openingAccumulated,
                'book_value' => (float) $asset->purchase_cost - $openingAccumulated,
            ])->saveQuietly();

            // `asset_id` dan `plate_number` sama-sama unik; cocokkan keduanya
            // agar unit lama dengan plat yang sama tidak memicu duplikat.
            $unit = VehicleUnit::query()
                ->where('asset_id', $asset->id)
                ->orWhere('plate_number', $vehicle['plate'])
                ->first()
                ?? new VehicleUnit;

            $unit->fill([
                'asset_id' => $asset->id,
                'plate_number' => $vehicle['plate'],
                'vehicle_type' => $vehicle['type'],
                'capacity' => $vehicle['capacity'],
                'is_active' => true,
            ])->save();

            $units[] = $unit;
        }

        return $units;
    }
}
