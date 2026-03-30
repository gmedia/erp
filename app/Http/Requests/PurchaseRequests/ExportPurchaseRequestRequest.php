<?php

namespace App\Http\Requests\PurchaseRequests;

class ExportPurchaseRequestRequest extends AbstractPurchaseRequestListingRequest
{
    public function rules(): array
    {
        return $this->purchaseRequestListingRules(
            'branch',
            'department',
            'pr_number,request_date,required_date,priority,status,estimated_amount,created_at',
        );
    }
}
