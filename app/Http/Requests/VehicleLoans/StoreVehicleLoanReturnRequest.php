<?php

namespace App\Http\Requests\VehicleLoans;

use App\Http\Requests\AuthorizedFormRequest;

class StoreVehicleLoanReturnRequest extends AuthorizedFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'handover_photo' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'condition_photo' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'incident' => ['required', 'boolean'],
            'incident_note' => ['required_if:incident,true', 'nullable', 'string', 'max:2000'],
            'odometer_start' => ['nullable', 'integer', 'min:0'],
            'odometer_end' => ['nullable', 'integer', 'min:0', 'gte:odometer_start'],
        ];
    }
}
