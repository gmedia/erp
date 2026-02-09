<?php

namespace App\Http\Requests\AssetMovements;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'exists:assets,id'],
            'movement_type' => ['required', 'string', Rule::in(['acquired', 'transfer', 'assign', 'return', 'dispose', 'adjustment'])],
            'moved_at' => ['required', 'date'],
            'to_branch_id' => ['nullable', 'exists:branches,id', Rule::requiredIf(fn() => in_array($this->movement_type, ['transfer']))],
            'to_location_id' => ['nullable', 'exists:asset_locations,id', Rule::requiredIf(fn() => in_array($this->movement_type, ['transfer']))],
            'to_department_id' => ['nullable', 'exists:departments,id', Rule::requiredIf(fn() => in_array($this->movement_type, ['assign']))],
            'to_employee_id' => ['nullable', 'exists:employees,id', Rule::requiredIf(fn() => in_array($this->movement_type, ['assign']))],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
