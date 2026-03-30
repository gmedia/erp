<?php

namespace App\Http\Requests\PurchaseRequests;

class IndexPurchaseRequestRequest extends AbstractPurchaseRequestListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->purchaseRequestListingRules(
                'branch_id',
                'department_id',
                'id,pr_number,branch,branch_id,department,department_id,requester,requested_by,request_date,' .
                    'required_date,priority,status,estimated_amount,created_at,updated_at',
            ),
            [
                'estimated_amount_min' => ['nullable', 'numeric', 'min:0'],
                'estimated_amount_max' => ['nullable', 'numeric', 'min:0'],
                'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
                'page' => ['nullable', 'integer', 'min:1'],
            ],
        );
    }
}
