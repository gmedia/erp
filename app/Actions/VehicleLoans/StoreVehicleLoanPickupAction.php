<?php

namespace App\Actions\VehicleLoans;

use App\Models\VehicleLoan;
use App\Models\VehicleLoanPickup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StoreVehicleLoanPickupAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(VehicleLoan $loan, array $data): VehicleLoanPickup
    {
        return DB::transaction(function () use ($loan, $data) {
            if ($loan->status !== 'approved') {
                throw new InvalidArgumentException('Only approved loans can be picked up.');
            }

            if ($loan->pickup) {
                throw new InvalidArgumentException('Pickup has already been submitted.');
            }

            $pickup = VehicleLoanPickup::create([
                'vehicle_loan_id' => $loan->id,
                'requester_id' => Auth::id(),
                'photo_mode' => $data['photo_mode'],
                'selfie_photo_path' => $data['selfie_photo_path'],
                'selfie_photo_name' => $data['selfie_photo_name'],
                'driver_snapshot_id' => $loan->driver_id,
                'unit_snapshot_id' => $loan->vehicle_unit_id,
                'picked_at' => now(),
            ]);

            $loan->update(['status' => 'pickup_done']);

            return $pickup;
        });
    }
}
