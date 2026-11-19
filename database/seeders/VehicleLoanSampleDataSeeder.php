<?php

namespace Database\Seeders;

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
use App\Models\Setting;
use App\Models\User;
use App\Models\VehicleDriver;
use App\Models\VehicleLoan;
use App\Models\VehicleLoanActivityLog;
use App\Models\VehicleLoanPickup;
use App\Models\VehicleLoanReturn;
use App\Models\VehicleUnit;
use Illuminate\Database\Seeder;

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
    public function run(): void
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

        $requesters = User::query()
            ->where('name', 'not like', '%sample%')
            ->where('name', 'not like', '%dummy%')
            ->where('name', 'not like', '%test%')
            ->orderBy('id')
            ->limit(5)
            ->pluck('id')
            ->all();

        if ($requesters === []) {
            $requesters = [$admin->id];
        }

        $driver = $this->firstDriver($admin);
        $unit = $this->firstUnit();

        if (! $unit) {
            // Master data tidak bisa dibuat aman tanpa Employee/Asset kategori
            // kendaraan. Seeder lain wajib berjalan lebih dulu.
            return;
        }

        $branchId = Branch::query()->orderBy('id')->value('id');
        $companyId = Company::query()->orderBy('id')->value('id');
        $departmentId = Department::query()->orderBy('id')->value('id');

        $scenarios = [
            ['code' => 'VL-2026-0001', 'status' => 'draft', 'purpose' => 'Perjalanan dinas ke Kantor Cabang Bandung'],
            ['code' => 'VL-2026-0002', 'status' => 'pending', 'purpose' => 'Antar dokumen kontrak ke kantor notaris'],
            ['code' => 'VL-2026-0003', 'status' => 'in_progress', 'purpose' => 'Kunjungan koordinasi proyek di Gedung Menara Kuningan'],
            ['code' => 'VL-2026-0004', 'status' => 'approved', 'purpose' => 'Penjemputan tamu dari Bandara Soekarno-Hatta'],
            ['code' => 'VL-2026-0005', 'status' => 'rejected', 'purpose' => 'Survey lokasi gudang di Cikarang'],
            ['code' => 'VL-2026-0006', 'status' => 'pickup_done', 'purpose' => 'Pengiriman sparepart ke vendor di Tangerang'],
            ['code' => 'VL-2026-0007', 'status' => 'returned', 'purpose' => 'Rapat koordinasi bulanan di kantor pusat'],
            ['code' => 'VL-2026-0008', 'status' => 'cancelled', 'purpose' => 'Kunjungan supplier ke pabrik Karawang'],
            ['code' => 'VL-2026-0009', 'status' => 'pickup_done', 'purpose' => 'Perjalanan dinas ke Surabaya', 'overdue' => true],
        ];

        foreach ($scenarios as $index => $scenario) {
            $loan = $this->seedLoan($scenario, $index, $requesters, $driver, $unit, [
                'branch_id' => $branchId,
                'company_id' => $companyId,
                'department_id' => $departmentId,
            ]);

            $this->seedApproval($loan, $scenario['status'], $flow, $flowStep, $admin, $requesters, $driver, $unit);
            $this->seedPickupAndReturn($loan, $scenario['status'], $requesters, $admin, $driver, $unit, (bool) ($scenario['overdue'] ?? false));
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

        $start = $overdue
            ? now()->subDays(3)->setTime(8, 0)
            : now()->addDay()->setTime(8, 0);
        $end = $overdue
            ? now()->subDay()->setTime(17, 0)
            : (clone $start)->setTime(17, 0);

        $submittedAt = in_array($status, ['draft'], true) ? null : (clone $start)->subDay()->setTime(9, 0);
        $approvedAt = in_array($status, ['approved', 'pickup_done', 'returned'], true)
            ? (clone $submittedAt)->addHours(2)
            : null;

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

            VehicleLoanActivityLog::firstOrCreate(
                [
                    'vehicle_loan_id' => $loan->id,
                    'event' => 'assignment_changed',
                ],
                [
                    'actor_user_id' => $this->approverId(),
                    'metadata' => [
                        'after' => ['driver_id' => $driver->id, 'vehicle_unit_id' => $unit->id],
                    ],
                ],
            );
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
    ): void {
        if (! in_array($status, ['pickup_done', 'returned'], true)) {
            return;
        }

        $pickedAt = $overdue
            ? now()->subDays(3)->setTime(8, 0)
            : (clone $loan->start_at)->setTime(7, 45);

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
                'key_handover_at' => (clone $pickedAt)->addMinutes(15),
                'security_user_id' => $admin->id,
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
            'in_progress' => ['approval_step_advanced'],
            'approved' => ['approval_completed'],
            'rejected' => ['approval_rejected'],
            'pickup_done' => ['pickup_submitted', 'key_handed_over'],
            'returned' => ['pickup_submitted', 'key_handed_over', 'return_submitted'],
            'cancelled' => ['cancelled'],
            default => [],
        };

        foreach ($events as $event) {
            VehicleLoanActivityLog::firstOrCreate(
                [
                    'vehicle_loan_id' => $loan->id,
                    'event' => $event,
                ],
                ['actor_user_id' => $admin->id],
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

    private function firstDriver(User $admin): VehicleDriver
    {
        $driver = VehicleDriver::where('license_number', 'SIM-B-2024-00123')->first();
        if ($driver) {
            return $driver;
        }

        $employee = Employee::firstOrCreate(
            ['employee_id' => 'EMP-2021-0145'],
            ['name' => 'Budi Santoso', 'email' => 'budi.santoso@example.com', 'user_id' => $admin->id],
        );

        return VehicleDriver::updateOrCreate(
            ['license_number' => 'SIM-B-2024-00123'],
            [
                'employee_id' => $employee->id,
                'license_type' => 'B',
                'license_expiry' => now()->addYears(5)->toDateString(),
                'is_active' => true,
            ]);
    }

    private function firstUnit(): ?VehicleUnit
    {
        $unit = VehicleUnit::where('plate_number', 'B 1234 XYZ')->first();
        if ($unit) {
            return $unit;
        }

        $category = AssetCategory::where('code', 'KND')->first()
            ?? AssetCategory::query()->orderBy('id')->first();
        $branchId = Branch::query()->orderBy('id')->value('id');

        if (! $category || ! $branchId) {
            return null;
        }

        $asset = Asset::updateOrCreate(
            ['asset_code' => 'FA-2021-0145'],
            [
                'name' => 'Toyota Avanza 1.5 G',
                'asset_category_id' => $category->id,
                'branch_id' => $branchId,
                'purchase_date' => now()->subYear()->toDateString(),
                'purchase_cost' => 150000000,
                'currency' => 'IDR',
                'status' => 'active',
                'condition' => 'good',
            ],
        );

        return VehicleUnit::updateOrCreate(
            ['asset_id' => $asset->id],
            [
                'plate_number' => 'B 1234 XYZ',
                'vehicle_type' => 'car',
                'capacity' => 5,
                'is_active' => true,
            ],
        );
    }
}
