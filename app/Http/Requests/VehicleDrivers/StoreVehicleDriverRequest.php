<?php

namespace App\Http\Requests\VehicleDrivers;

use App\Http\Requests\AuthorizedFormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleDriverRequest extends AuthorizedFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id', Rule::unique('vehicle_drivers', 'employee_id')],
            'license_number' => ['nullable', 'string', 'max:100'],
            'license_type' => ['nullable', 'string', 'max:10'],
            'license_expiry' => ['nullable', 'date', 'after:today'],
            'is_active' => ['boolean'],
        ];
    }
}
