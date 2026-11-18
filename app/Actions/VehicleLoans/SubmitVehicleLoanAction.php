<?php

namespace App\Actions\VehicleLoans;

use App\Actions\Approvals\TriggerApprovalAction;
use App\Models\VehicleLoan;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SubmitVehicleLoanAction
{
    public function __construct(
        private readonly TriggerApprovalAction $triggerApproval,
    ) {}

    public function execute(VehicleLoan $loan): void
    {
        DB::transaction(function () use ($loan): void {
            if (! in_array($loan->status, ['draft', 'rejected'], true)) {
                throw new InvalidArgumentException('Only draft or rejected loans can be submitted.');
            }

            app(ValidateVehicleLoanAction::class)->execute($loan->only([
                'start_at', 'end_at', 'purpose', 'driver_id', 'vehicle_unit_id',
            ]), $loan->id);

            $approval = $this->triggerApproval->execute($loan, [
                'flow_code' => config('vehicle_loan.approval_flow_code'),
            ]);

            if (! $approval) {
                throw new InvalidArgumentException('No active approval flow is configured for vehicle loans.');
            }

            $loan->update([
                'status' => 'pending',
                'submitted_at' => now(),
                'rejection_reason' => null,
            ]);
        });
    }
}
