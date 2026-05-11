<?php

namespace App\Http\Requests\CreditNotes;

class IndexCreditNoteRequest extends AbstractCreditNoteListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->creditNoteListingRules('customer_id', 'branch_id'),
            [
                'fiscal_year_id' => ['nullable', 'integer', 'exists:fiscal_years,id'],
                'customer_invoice_id' => ['nullable', 'integer', 'exists:customer_invoices,id'],
                'grand_total_min' => ['nullable', 'numeric', 'min:0'],
                'grand_total_max' => ['nullable', 'numeric', 'min:0'],
            ],
            $this->listingSortRules(
                'id,credit_note_number,customer,customer_id,branch,branch_id,credit_note_date,' .
                    'reason,status,grand_total,created_at,updated_at'
            ),
            $this->paginationRules(),
        );
    }
}
