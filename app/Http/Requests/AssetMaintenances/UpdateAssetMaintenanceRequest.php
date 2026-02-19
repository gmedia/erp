<?php

namespace App\Http\Requests\AssetMaintenances;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_id' => ['sometimes', 'required', 'exists:assets,id'],
            'maintenance_type' => ['sometimes', 'required', 'string', Rule::in(['preventive', 'corrective', 'calibration', 'other'])],
            'status' => ['sometimes', 'required', 'string', Rule::in(['scheduled', 'in_progress', 'completed', 'cancelled'])],
            'scheduled_at' => ['sometimes', 'nullable', 'date'],
            'performed_at' => ['sometimes', 'nullable', 'date'],
            'supplier_id' => ['sometimes', 'nullable', 'exists:suppliers,id'],
            'cost' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
