<?php

namespace App\Http\Requests\ApprovalDelegations;

class IndexApprovalDelegationRequest extends AbstractApprovalDelegationListingRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(
            $this->approvalDelegationBaseRules(
                'delegator_user_id',
                'delegate_user_id',
            ),
            [
                'start_date_from' => ['nullable', 'date'],
                'start_date_to' => ['nullable', 'date'],
            ],
            $this->approvalDelegationSortRules(
                'id,delegator_user_id,delegate_user_id,approvable_type,start_date,end_date,is_active,created_at,updated_at',
            ),
            [
                'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
                'page' => ['nullable', 'integer', 'min:1'],
            ],
        );
    }
}
