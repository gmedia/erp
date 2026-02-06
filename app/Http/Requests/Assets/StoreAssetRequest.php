<?php

namespace App\Http\Requests\Assets;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_code' => ['required', 'string', 'max:255', 'unique:assets,asset_code'],
            'name' => ['required', 'string', 'max:255'],
            'asset_category_id' => ['required', 'exists:asset_categories,id'],
            'asset_model_id' => ['nullable', 'exists:asset_models,id'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255', 'unique:assets,barcode'],
            'branch_id' => ['required', 'exists:branches,id'],
            'asset_location_id' => ['nullable', 'exists:asset_locations,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'purchase_date' => ['required', 'date'],
            'purchase_cost' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'warranty_end_date' => ['nullable', 'date', 'after_or_equal:purchase_date'],
            'status' => ['required', 'string', 'in:draft,active,maintenance,disposed,lost'],
            'condition' => ['nullable', 'string', 'in:good,needs_repair,damaged'],
            'notes' => ['nullable', 'string'],
            'depreciation_method' => ['required', 'string', 'in:straight_line,declining_balance'],
            'depreciation_start_date' => ['nullable', 'date'],
            'useful_life_months' => ['nullable', 'integer', 'min:0'],
            'salvage_value' => ['nullable', 'numeric', 'min:0'],
            'depreciation_expense_account_id' => ['nullable', 'exists:accounts,id'],
            'accumulated_depr_account_id' => ['nullable', 'exists:accounts,id'],
        ];
    }
}
