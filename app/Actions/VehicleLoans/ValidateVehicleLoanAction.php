<?php

namespace App\Actions\VehicleLoans;

use App\Models\VehicleDriver;
use App\Models\VehicleLoan;
use Carbon\Carbon;
use InvalidArgumentException;

class ValidateVehicleLoanAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?int $ignoreLoanId = null): void
    {
        if (strlen(trim((string) ($data['purpose'] ?? ''))) < 5) {
            throw new InvalidArgumentException('Purpose must be at least 5 characters.');
        }

        $start = Carbon::parse($data['start_at'])->utc();
        $end = Carbon::parse($data['end_at'])->utc();

        if ($end->lessThanOrEqualTo($start)) {
            throw new InvalidArgumentException('End time must be after start time.');
        }

        if (empty($data['skip_future']) && $start->lessThan(now())) {
            throw new InvalidArgumentException('Start time must be in the future.');
        }

        if (! empty($data['driver_id'])) {
            $driver = VehicleDriver::find($data['driver_id']);

            if (! $driver?->is_active) {
                throw new InvalidArgumentException('Selected driver is not active.');
            }

            if ($driver->license_expiry && $driver->license_expiry->lt($start->toDateString())) {
                throw new InvalidArgumentException('Selected driver license has expired.');
            }
        }

        $activeStatuses = ['pending', 'in_progress', 'approved', 'pickup_done'];

        $query = VehicleLoan::query()
            ->whereIn('status', $activeStatuses)
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start)
            ->lockForUpdate();

        if ($ignoreLoanId !== null) {
            $query->where('id', '!=', $ignoreLoanId);
        }

        if (! empty($data['vehicle_unit_id'])) {
            $unitConflict = (clone $query)->where('vehicle_unit_id', $data['vehicle_unit_id'])->exists();

            if ($unitConflict) {
                throw new InvalidArgumentException('Selected vehicle unit is already booked for this period.');
            }
        }

        if (! empty($data['driver_id'])) {
            $driverConflict = (clone $query)->where('driver_id', $data['driver_id'])->exists();

            if ($driverConflict) {
                throw new InvalidArgumentException('Selected driver is already booked for this period.');
            }
        }
    }
}
