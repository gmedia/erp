<?php

namespace App\Http\Requests\SupplierReturns;

class ExportSupplierReturnRequest extends AbstractSupplierReturnListingRequest
{
    public function rules(): array
    {
        return $this->supplierReturnListingRules(
            'return_number,return_date,reason,status,created_at',
            'purchase_order',
            'goods_receipt',
            'supplier',
            'warehouse',
        );
    }
}
