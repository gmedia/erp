<?php

namespace App\Actions\VehicleLoans;

use App\Actions\Approvals\ResolveApprovalStepActorAction;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStep;
use App\Models\VehicleLoan;
use App\Models\VehicleLoanActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReviewVehicleLoanAssignmentAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(VehicleLoan $loan, ApprovalRequest $approval, array $data): void
    {
        DB::transaction(function () use ($loan, $approval, $data): void {
            $userId = Auth::id();

            /** @var ApprovalRequestStep|null $step */
            $step = app(ResolveApprovalStepActorAction::class)->execute($approval, (int) $userId);

            if (! $step || ! in_array($approval->status, ['pending', 'in_progress'], true)) {
                throw new InvalidArgumentException('You are not authorized to review this assignment.');
            }

            $before = [
                'driver_id' => $loan->driver_id,
                'vehicle_unit_id' => $loan->vehicle_unit_id,
            ];

            $newDriverId = $data['driver_id'] ?? $loan->driver_id;
            $newUnitId = $data['vehicle_unit_id'] ?? $loan->vehicle_unit_id;
            $assignmentChanged = (int) ($newDriverId ?? 0) !== (int) ($loan->driver_id ?? 0)
                || (int) ($newUnitId ?? 0) !== (int) ($loan->vehicle_unit_id ?? 0);

            if ($assignmentChanged) {
                app(ValidateVehicleLoanAction::class)->execute([
                    'start_at' => $loan->start_at,
                    'end_at' => $loan->end_at,
                    'purpose' => $loan->purpose,
                    'driver_id' => $newDriverId,
                    'vehicle_unit_id' => $newUnitId,
                    'skip_future' => true,
                ], $loan->id);
            }

            $loan->update([
                'driver_id' => $data['driver_id'] ?? $loan->driver_id,
                'vehicle_unit_id' => $data['vehicle_unit_id'] ?? $loan->vehicle_unit_id,
                'review_notes' => $data['review_notes'] ?? null,
            ]);

            VehicleLoanActivityLog::create([
                'vehicle_loan_id' => $loan->id,
                'event' => 'assignment_changed',
                'actor_user_id' => $userId,
                'metadata' => ['before' => $before, 'after' => [
                    'driver_id' => $loan->driver_id,
                    'vehicle_unit_id' => $loan->vehicle_unit_id,
                ]],
            ]);
        });
    }
}
