<?php

namespace App\Http\Requests\AssetMovements;

use Illuminate\Foundation\Http\FormRequest;

class ExportAssetMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'asset_id' => ['nullable', 'exists:assets,id'],
            'movement_type' => ['nullable', 'string', 'in:acquired,transfer,assign,return,dispose,adjustment'],
            'from_branch_id' => ['nullable', 'exists:branches,id'],
            'to_branch_id' => ['nullable', 'exists:branches,id'],
            'from_employee_id' => ['nullable', 'exists:employees,id'],
            'to_employee_id' => ['nullable', 'exists:employees,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'sort_by' => ['nullable', 'string', 'in:moved_at,movement_type,created_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
