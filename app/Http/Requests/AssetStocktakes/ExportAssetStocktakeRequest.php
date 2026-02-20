<?php

namespace App\Http\Requests\AssetStocktakes;

use Illuminate\Foundation\Http\FormRequest;

class ExportAssetStocktakeRequest extends FormRequest
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
            'status' => ['nullable', 'in:draft,in_progress,completed,cancelled'],
            'sort_by' => ['nullable', 'string', 'in:id,reference,planned_at,performed_at,status,created_at,updated_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
