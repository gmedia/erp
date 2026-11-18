<?php

namespace App\Actions\VehicleLoans;

use App\Models\VehicleLoanPickup;
use App\Models\VehicleLoanReturn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StoreVehicleLoanReturnAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(VehicleLoanPickup $pickup, array $data): VehicleLoanReturn
    {
        return DB::transaction(function () use ($pickup, $data) {
            if ($pickup->key_handover_at === null) {
                throw new InvalidArgumentException('Key has not been handed over by security.');
            }

            if ($pickup->returnRecord) {
                throw new InvalidArgumentException('Return has already been submitted.');
            }

            $return = VehicleLoanReturn::create([
                'pickup_id' => $pickup->id,
                'vehicle_loan_id' => $pickup->vehicle_loan_id,
                'returner_id' => Auth::id(),
                'handover_photo_path' => $data['handover_photo_path'],
                'handover_photo_name' => $data['handover_photo_name'],
                'condition_photo_path' => $data['condition_photo_path'],
                'condition_photo_name' => $data['condition_photo_name'],
                'incident' => $data['incident'] ?? false,
                'incident_note' => $data['incident_note'] ?? null,
                'odometer_start' => $data['odometer_start'] ?? null,
                'odometer_end' => $data['odometer_end'] ?? null,
                'returned_at' => now(),
            ]);

            $pickup->loan->update(['status' => 'returned']);

            return $return;
        });
    }
}
