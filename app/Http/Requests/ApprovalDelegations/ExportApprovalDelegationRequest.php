<?php

namespace App\Http\Requests\ApprovalDelegations;

use Illuminate\Foundation\Http\FormRequest;

class ExportApprovalDelegationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'delegator' => ['nullable', 'exists:users,id'],
            'delegate' => ['nullable', 'exists:users,id'],
            'is_active' => ['nullable', 'string', 'in:true,false,1,0'],
            'sort_by' => [
                'nullable',
                'string',
                'in:id,delegator_user_id,delegate_user_id,approvable_type,start_date,end_date,is_active,created_at',
            ],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
