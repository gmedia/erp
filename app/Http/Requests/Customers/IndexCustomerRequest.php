<?php

namespace App\Http\Requests\Customers;

class IndexCustomerRequest extends AbstractCustomerListingRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge(
            $this->customerListingRules('id,name,email,phone,address,branch_id,category_id,status,created_at,updated_at'),
            $this->paginationRules(),
        );
    }
}
