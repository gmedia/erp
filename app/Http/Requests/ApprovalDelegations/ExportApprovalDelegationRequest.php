<?php

namespace App\Http\Requests\ApprovalDelegations;

class ExportApprovalDelegationRequest extends AbstractApprovalDelegationListingRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(
            $this->approvalDelegationBaseRules('delegator', 'delegate'),
            $this->approvalDelegationSortRules(
                'id,delegator_user_id,delegate_user_id,approvable_type,start_date,end_date,is_active,created_at',
            ),
        );
    }
}
