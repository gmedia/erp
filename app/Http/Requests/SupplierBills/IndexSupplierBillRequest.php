<?php

namespace App\Http\Requests\SupplierBills;

class IndexSupplierBillRequest extends AbstractSupplierBillListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->supplierBillListingRules('supplier_id', 'branch_id'),
            [
                'fiscal_year_id' => ['nullable', 'integer', 'exists:fiscal_years,id'],
                'grand_total_min' => ['nullable', 'numeric', 'min:0'],
                'grand_total_max' => ['nullable', 'numeric', 'min:0'],
                'amount_due_min' => ['nullable', 'numeric', 'min:0'],
                'amount_due_max' => ['nullable', 'numeric', 'min:0'],
            ],
            $this->listingSortRules(
                'id,bill_number,supplier,supplier_id,branch,branch_id,bill_date,due_date,' .
                    'currency,status,grand_total,amount_paid,amount_due,created_at,updated_at'
            ),
            $this->paginationRules(),
        );
    }
}
