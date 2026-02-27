<?php

namespace App\Http\Requests\ApprovalFlows;

use Illuminate\Foundation\Http\FormRequest;

class ExportApprovalFlowRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'approvable_type' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'sort_by' => ['nullable', 'string', 'in:name,code,approvable_type,is_active,created_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
