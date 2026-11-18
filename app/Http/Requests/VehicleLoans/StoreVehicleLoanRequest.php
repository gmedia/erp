<?php

namespace App\Http\Requests\VehicleLoans;

use App\Http\Requests\AuthorizedFormRequest;
use Carbon\Carbon;

class StoreVehicleLoanRequest extends AuthorizedFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after:start_at'],
            'purpose' => ['nullable', 'string', 'max:1000'],
            'driver_id' => ['nullable', 'exists:vehicle_drivers,id'],
            'vehicle_unit_id' => ['nullable', 'exists:vehicle_units,id'],
        ];
    }

    /**
     * Normalize local datetime strings to UTC before validation/use.
     */
    protected function prepareForValidation(): void
    {
        foreach (['start_at', 'end_at'] as $key) {
            if ($this->filled($key)) {
                $this->merge([$key => Carbon::parse($this->input($key))->utc()->toIso8601String()]);
            }
        }
    }
}
