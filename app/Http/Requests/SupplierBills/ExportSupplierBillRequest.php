<?php

namespace App\Http\Requests\SupplierBills;

class ExportSupplierBillRequest extends AbstractSupplierBillListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->supplierBillListingRules('supplier', 'branch'),
            $this->listingSortRules('bill_number,bill_date,due_date,currency,status,grand_total,amount_paid,amount_due,created_at'),
        );
    }
}
