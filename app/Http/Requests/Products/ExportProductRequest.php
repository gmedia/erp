<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

class ExportProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:product_categories,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'type' => ['nullable', 'in:raw_material,work_in_progress,finished_good,purchased_good,service'],
            'status' => ['nullable', 'in:active,inactive,discontinued'],
            'billing_model' => ['nullable', 'in:one_time,subscription,both'],
            'sort_by' => ['nullable', 'string', 'in:code,name,type,cost,selling_price,status,created_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
