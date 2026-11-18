<?php

namespace App\Actions\VehicleLoans;

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
            $step = $approval->steps()
                ->where('status', 'pending')
                ->where('step_order', $approval->current_step_order)
                ->whereHas('flowStep', fn ($q) => $q
                    ->where('approver_type', 'user')
                    ->where('approver_user_id', $userId))
                ->lockForUpdate()
                ->first();

            if (! $step || ! in_array($approval->status, ['pending', 'in_progress'], true)) {
                throw new InvalidArgumentException('You are not authorized to review this assignment.');
            }

            $before = [
                'driver_id' => $loan->driver_id,
                'vehicle_unit_id' => $loan->vehicle_unit_id,
            ];

            app(ValidateVehicleLoanAction::class)->execute([
                'start_at' => $loan->start_at,
                'end_at' => $loan->end_at,
                'purpose' => $loan->purpose,
                'driver_id' => $data['driver_id'] ?? $loan->driver_id,
                'vehicle_unit_id' => $data['vehicle_unit_id'] ?? $loan->vehicle_unit_id,
                'skip_future' => true,
            ], $loan->id);

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
