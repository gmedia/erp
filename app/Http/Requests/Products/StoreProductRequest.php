<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', 'unique:products,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:raw_material,work_in_progress,finished_good,purchased_good,service'],
            'category_id' => ['required', 'exists:product_categories,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'cost' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'markup_percentage' => ['nullable', 'numeric', 'min:0'],
            'billing_model' => ['required', 'in:one_time,subscription,both'],
            'is_recurring' => ['required', 'boolean'],
            'trial_period_days' => ['nullable', 'integer', 'min:0'],
            'allow_one_time_purchase' => ['required', 'boolean'],
            'is_manufactured' => ['required', 'boolean'],
            'is_purchasable' => ['required', 'boolean'],
            'is_sellable' => ['required', 'boolean'],
            'is_taxable' => ['required', 'boolean'],
            'status' => ['required', 'in:active,inactive,discontinued'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
