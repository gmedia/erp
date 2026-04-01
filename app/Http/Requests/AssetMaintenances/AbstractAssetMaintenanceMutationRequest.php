<?php

namespace App\Http\Requests\AssetMaintenances;

use App\Http\Requests\Concerns\HasSometimesArrayRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class AbstractAssetMaintenanceMutationRequest extends FormRequest
{
    use HasSometimesArrayRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_id' => $this->withSometimes(['required', 'exists:assets,id']),
            'maintenance_type' => $this->withSometimes([
                'required',
                'string',
                Rule::in(['preventive', 'corrective', 'calibration', 'other']),
            ]),
            'status' => $this->withSometimes([
                'required',
                'string',
                Rule::in(['scheduled', 'in_progress', 'completed', 'cancelled']),
            ]),
            'scheduled_at' => $this->withSometimes(['nullable', 'date']),
            'performed_at' => $this->withSometimes(['nullable', 'date']),
            'supplier_id' => $this->withSometimes(['nullable', 'exists:suppliers,id']),
            'cost' => $this->withSometimes(['nullable', 'numeric', 'min:0']),
            'notes' => $this->withSometimes(['nullable', 'string']),
        ];
    }

    abstract protected function usesSometimes(): bool;
}
