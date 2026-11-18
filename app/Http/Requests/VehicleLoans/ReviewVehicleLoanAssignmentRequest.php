<?php

namespace App\Http\Requests\VehicleLoans;

use App\Http\Requests\AuthorizedFormRequest;

class ReviewVehicleLoanAssignmentRequest extends AuthorizedFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'driver_id' => ['nullable', 'exists:vehicle_drivers,id'],
            'vehicle_unit_id' => ['nullable', 'exists:vehicle_units,id'],
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
