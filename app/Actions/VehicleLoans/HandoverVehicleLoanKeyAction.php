<?php

namespace App\Actions\VehicleLoans;

use App\Models\ApprovalRequest;
use App\Models\VehicleLoanActivityLog;
use App\Models\VehicleLoanPickup;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class HandoverVehicleLoanKeyAction
{
    public function execute(VehicleLoanPickup $pickup): void
    {
        if ($pickup->key_handover_at !== null) {
            throw new InvalidArgumentException('Key has already been handed over.');
        }

        // Source of truth is the approval table, never the mirrored loan status.
        /** @var ApprovalRequest|null $approval */
        $approval = $pickup->loan->approvalRequests()
            ->latest()
            ->first();

        if (! $approval || $approval->status !== 'approved') {
            throw new InvalidArgumentException('Loan is not approved. Key must not be handed over.');
        }

        $pickup->update([
            'key_handover_at' => now(),
            'security_user_id' => Auth::id(),
        ]);

        VehicleLoanActivityLog::create([
            'vehicle_loan_id' => $pickup->vehicle_loan_id,
            'event' => 'key_handed_over',
            'actor_user_id' => Auth::id(),
        ]);
    }
}
