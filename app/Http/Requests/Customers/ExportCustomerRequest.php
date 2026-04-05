<?php

namespace App\Http\Requests\Customers;

class ExportCustomerRequest extends AbstractCustomerListingRequest
{
    public function rules(): array
    {
        return $this->customerListingRules('id,name,email,phone,address,branch_id,category_id,status,created_at,updated_at');
    }
}
