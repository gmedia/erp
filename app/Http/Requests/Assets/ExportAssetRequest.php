<?php

namespace App\Http\Requests\Assets;

use Illuminate\Foundation\Http\FormRequest;

class ExportAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'branch' => ['nullable', 'exists:branches,id'],
            'category' => ['nullable', 'exists:asset_categories,id'],
            'status' => ['nullable', 'string', 'in:draft,active,maintenance,disposed,lost'],
            'sort_by' => ['nullable', 'string', 'in:asset_code,name,purchase_date,status'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
