<?php

namespace App\Exports;

use App\Exports\Concerns\BaseExport;
use App\Models\VehicleLoan;
use Illuminate\Database\Eloquent\Builder;

class VehicleLoanExport extends BaseExport
{
    public function query(): Builder
    {
        $query = VehicleLoan::query()->with([
            'requester',
            'department',
            'driver.employee',
            'vehicleUnit.asset',
            'pickup',
            'returns',
        ]);

        $filters = $this->filters;

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('start_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('end_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (! empty($filters['requester_id'])) {
            $query->where('requester_id', $filters['requester_id']);
        }

        return $query->orderByDesc('id');
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'Code' => fn (VehicleLoan $loan): mixed => $loan->code,
            'Requester' => fn (VehicleLoan $loan): mixed => $loan->requester->name,
            'Department' => fn (VehicleLoan $loan): mixed => $loan->department?->name,
            'Start At' => fn (VehicleLoan $loan): mixed => $loan->start_at?->toIso8601String(),
            'End At' => fn (VehicleLoan $loan): mixed => $loan->end_at?->toIso8601String(),
            'Purpose' => fn (VehicleLoan $loan): mixed => $loan->purpose,
            'Driver' => fn (VehicleLoan $loan): mixed => $loan->driver?->employee->name,
            'Plate Number' => fn (VehicleLoan $loan): mixed => $loan->vehicleUnit?->plate_number,
            'Status' => fn (VehicleLoan $loan): mixed => $loan->status,
            'Submitted At' => fn (VehicleLoan $loan): mixed => $loan->submitted_at?->toIso8601String(),
            'Approved At' => fn (VehicleLoan $loan): mixed => $loan->approved_at?->toIso8601String(),
            'Rejection Reason' => fn (VehicleLoan $loan): mixed => $loan->rejection_reason,
            'Pickup At' => fn (VehicleLoan $loan): mixed => $loan->pickup?->picked_at->toIso8601String(),
            'Returned At' => fn (VehicleLoan $loan): mixed => $loan->returns?->returned_at->toIso8601String(),
            'Incident' => fn (VehicleLoan $loan): mixed => $loan->returns?->incident ? 'Yes' : 'No',
        ];
    }
}
