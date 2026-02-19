<?php

namespace App\Http\Requests\AssetMaintenances;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'exists:assets,id'],
            'maintenance_type' => ['required', 'string', Rule::in(['preventive', 'corrective', 'calibration', 'other'])],
            'status' => ['required', 'string', Rule::in(['scheduled', 'in_progress', 'completed', 'cancelled'])],
            'scheduled_at' => ['nullable', 'date'],
            'performed_at' => ['nullable', 'date'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
