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
 * Sample data untuk seluruh tahapan modul Vehicle Loans.
 *
 * Setiap status didemokan oleh satu record: draft, pending, in_progress,
 * approved, rejected, pickup_done, returned, cancelled, ditambah satu
 * record pickup_done yang overdue. ApprovalRequest dibuat sebagai source of
 * truth, lengkap dengan step-nya, agar badge/approval queue konsisten dengan
 * status mirror di vehicle_loans.
 */
class VehicleLoanSampleDataSeeder extends Seeder
{
    private const PREFIX = 'VL-SAMPLE-';

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

        $requesters = User::query()->orderBy('id')->limit(5)->pluck('id')->all();
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
            ['code' => self::PREFIX . 'DRAFT', 'status' => 'draft', 'purpose' => 'Sample: permintaan masih draft'],
            ['code' => self::PREFIX . 'PENDING', 'status' => 'pending', 'purpose' => 'Sample: menunggu approval'],
            ['code' => self::PREFIX . 'IN-PROGRESS', 'status' => 'in_progress', 'purpose' => 'Sample: approval bertahap berjalan'],
            ['code' => self::PREFIX . 'APPROVED', 'status' => 'approved', 'purpose' => 'Sample: disetujui, siap ambil kunci'],
            ['code' => self::PREFIX . 'REJECTED', 'status' => 'rejected', 'purpose' => 'Sample: ditolak approver'],
            ['code' => self::PREFIX . 'PICKUP-DONE', 'status' => 'pickup_done', 'purpose' => 'Sample: kunci diambil, kendaraan dipakai'],
            ['code' => self::PREFIX . 'RETURNED', 'status' => 'returned', 'purpose' => 'Sample: sudah dikembalikan'],
            ['code' => self::PREFIX . 'CANCELLED', 'status' => 'cancelled', 'purpose' => 'Sample: dibatalkan peminjam'],
            ['code' => self::PREFIX . 'OVERDUE', 'status' => 'pickup_done', 'purpose' => 'Sample: terlewat jadwal pengembalian', 'overdue' => true],
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
            ? 'Sample: jadwal bentrok dengan kebutuhan unit lain.'
            : null;

        $reviewNotes = in_array($status, ['in_progress', 'approved'], true)
            ? 'Sample: driver dan unit disetujui setelah review.'
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
                    ? 'Sample: jadwal bentrok dengan kebutuhan unit lain.'
                    : ($status === 'in_progress' ? 'Sample: driver dan unit diperbarui.' : null),
                'acted_at' => $stepStatus === 'pending' ? null : ($loan->approved_at ?? now()),
            ],
        );

        if ($status === 'in_progress') {
            $loan->update([
                'review_notes' => 'Sample: driver dan unit diperbarui oleh approver.',
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
                'selfie_photo_path' => self::PREFIX . 'pickups/' . $loan->code . '.jpg',
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
                'handover_photo_path' => self::PREFIX . 'returns/' . $loan->code . '-handover.jpg',
                'handover_photo_name' => 'handover.jpg',
                'condition_photo_path' => self::PREFIX . 'returns/' . $loan->code . '-condition.jpg',
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
        $driver = VehicleDriver::query()->orderBy('id')->first();
        if ($driver) {
            return $driver;
        }

        $employee = Employee::query()->orderBy('id')->first();
        if (! $employee) {
            $employee = Employee::create([
                'employee_id' => 'EMP-SAMPLE-VL',
                'name' => 'Sample Driver',
                'user_id' => $admin->id,
            ]);
        }

        return VehicleDriver::create([
            'employee_id' => $employee->id,
            'license_number' => 'SIM-SAMPLE-001',
            'license_type' => 'B',
            'license_expiry' => now()->addYears(5)->toDateString(),
            'is_active' => true,
        ]);
    }

    private function firstUnit(): ?VehicleUnit
    {
        $unit = VehicleUnit::query()->orderBy('id')->first();
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
            ['asset_code' => 'FA-SAMPLE-VL-001'],
            [
                'name' => 'Sample Vehicle',
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
                'plate_number' => 'B-SAMPLE-1',
                'vehicle_type' => 'car',
                'capacity' => 5,
                'is_active' => true,
            ],
        );
    }
}
