<?php

namespace App\Http\Requests\VehicleUnits;

use App\Http\Requests\AuthorizedFormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleUnitRequest extends AuthorizedFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'exists:assets,id', Rule::unique('vehicle_units', 'asset_id')],
            'plate_number' => ['required', 'string', 'max:50', Rule::unique('vehicle_units', 'plate_number')],
            'vehicle_type' => ['required', 'string', 'in:car,motorcycle'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ];
    }
}
