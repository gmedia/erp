<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('products', 'code')->ignore($this->route('product')),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'type' => ['sometimes', 'required', 'in:raw_material,work_in_progress,finished_good,purchased_good,service'],
            'category_id' => ['sometimes', 'required', 'exists:product_categories,id'],
            'unit_id' => ['sometimes', 'required', 'exists:units,id'],
            'branch_id' => ['sometimes', 'nullable', 'exists:branches,id'],
            'cost' => ['sometimes', 'required', 'numeric', 'min:0'],
            'selling_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'markup_percentage' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'billing_model' => ['sometimes', 'required', 'in:one_time,subscription,both'],
            'is_recurring' => ['sometimes', 'required', 'boolean'],
            'trial_period_days' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'allow_one_time_purchase' => ['sometimes', 'required', 'boolean'],
            'is_manufactured' => ['sometimes', 'required', 'boolean'],
            'is_purchasable' => ['sometimes', 'required', 'boolean'],
            'is_sellable' => ['sometimes', 'required', 'boolean'],
            'is_taxable' => ['sometimes', 'required', 'boolean'],
            'status' => ['sometimes', 'required', 'in:active,inactive,discontinued'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
