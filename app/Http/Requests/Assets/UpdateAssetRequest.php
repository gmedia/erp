<?php

namespace App\Http\Requests\Assets;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $assetId = $this->route('asset');

        return [
            'asset_code' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('assets')->ignore($assetId)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'asset_category_id' => ['sometimes', 'required', 'exists:asset_categories,id'],
            'asset_model_id' => ['sometimes', 'nullable', 'exists:asset_models,id'],
            'serial_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('assets')->ignore($assetId)],
            'branch_id' => ['sometimes', 'required', 'exists:branches,id'],
            'asset_location_id' => ['sometimes', 'nullable', 'exists:asset_locations,id'],
            'department_id' => ['sometimes', 'nullable', 'exists:departments,id'],
            'employee_id' => ['sometimes', 'nullable', 'exists:employees,id'],
            'supplier_id' => ['sometimes', 'nullable', 'exists:suppliers,id'],
            'purchase_date' => ['sometimes', 'required', 'date'],
            'purchase_cost' => ['sometimes', 'required', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'required', 'string', 'size:3'],
            'warranty_end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:purchase_date'],
            'status' => ['sometimes', 'required', 'string', 'in:draft,active,maintenance,disposed,lost'],
            'condition' => ['sometimes', 'nullable', 'string', 'in:good,needs_repair,damaged'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'depreciation_method' => ['sometimes', 'required', 'string', 'in:straight_line,declining_balance'],
            'depreciation_start_date' => ['sometimes', 'nullable', 'date'],
            'useful_life_months' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'salvage_value' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'depreciation_expense_account_id' => ['sometimes', 'nullable', 'exists:accounts,id'],
            'accumulated_depr_account_id' => ['sometimes', 'nullable', 'exists:accounts,id'],
        ];
    }
}
