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
            'category' => ['nullable', 'exists:product_categories,id'],
            'unit' => ['nullable', 'exists:units,id'],
            'branch' => ['nullable', 'exists:branches,id'],
            'type' => ['nullable', 'in:raw_material,work_in_progress,finished_good,purchased_good,service'],
            'status' => ['nullable', 'in:active,inactive,discontinued'],
            'sort_by' => ['nullable', 'string', 'in:code,name,type,cost,selling_price,status,created_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
