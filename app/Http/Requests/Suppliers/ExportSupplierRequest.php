<?php

namespace App\Http\Requests\Suppliers;

class ExportSupplierRequest extends AbstractSupplierListingRequest
{
    public function rules(): array
    {
        return $this->supplierListingRules(
            'id,name,email,phone,branch,branch_id,category,category_id,status,created_at,updated_at',
            false,
        );
    }
}
