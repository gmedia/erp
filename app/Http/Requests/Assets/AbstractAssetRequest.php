<?php

namespace App\Http\Requests\Assets;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class AbstractAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_code' => $this->withSometimes(['required', 'string', 'max:255', $this->assetCodeUniqueRule()]),
            'name' => $this->withSometimes(['required', 'string', 'max:255']),
            'asset_category_id' => $this->withSometimes(['required', 'exists:asset_categories,id']),
            'asset_model_id' => $this->withSometimes(['nullable', 'exists:asset_models,id']),
            'serial_number' => $this->withSometimes(['nullable', 'string', 'max:255']),
            'barcode' => $this->withSometimes(['nullable', 'string', 'max:255', $this->barcodeUniqueRule()]),
            'branch_id' => $this->withSometimes(['required', 'exists:branches,id']),
            'asset_location_id' => $this->withSometimes(['nullable', 'exists:asset_locations,id']),
            'department_id' => $this->withSometimes(['nullable', 'exists:departments,id']),
            'employee_id' => $this->withSometimes(['nullable', 'exists:employees,id']),
            'supplier_id' => $this->withSometimes(['nullable', 'exists:suppliers,id']),
            'purchase_date' => $this->withSometimes(['required', 'date']),
            'purchase_cost' => $this->withSometimes(['required', 'numeric', 'min:0']),
            'currency' => $this->withSometimes(['required', 'string', 'size:3']),
            'warranty_end_date' => $this->withSometimes(['nullable', 'date', 'after_or_equal:purchase_date']),
            'status' => $this->withSometimes(['required', 'string', 'in:draft,active,maintenance,disposed,lost']),
            'condition' => $this->withSometimes(['nullable', 'string', 'in:good,needs_repair,damaged']),
            'notes' => $this->withSometimes(['nullable', 'string']),
            'depreciation_method' => $this->withSometimes(['required', 'string', 'in:straight_line,declining_balance']),
            'depreciation_start_date' => $this->withSometimes(['nullable', 'date']),
            'useful_life_months' => $this->withSometimes(['nullable', 'integer', 'min:0']),
            'salvage_value' => $this->withSometimes(['nullable', 'numeric', 'min:0']),
            'depreciation_expense_account_id' => $this->withSometimes(['nullable', 'exists:accounts,id']),
            'accumulated_depr_account_id' => $this->withSometimes(['nullable', 'exists:accounts,id']),
        ];
    }

    /**
     * @param  array<int, string|object>  $rules
     * @return array<int, string|object>
     */
    private function withSometimes(array $rules): array
    {
        if (! $this->isUpdateRequest()) {
            return $rules;
        }

        return ['sometimes', ...$rules];
    }

    private function assetCodeUniqueRule(): string|Rule
    {
        if (! $this->isUpdateRequest()) {
            return 'unique:assets,asset_code';
        }

        return Rule::unique('assets')->ignore($this->route('asset'));
    }

    private function barcodeUniqueRule(): string|Rule
    {
        if (! $this->isUpdateRequest()) {
            return 'unique:assets,barcode';
        }

        return Rule::unique('assets')->ignore($this->route('asset'));
    }

    private function isUpdateRequest(): bool
    {
        return $this instanceof UpdateAssetRequest;
    }
}
