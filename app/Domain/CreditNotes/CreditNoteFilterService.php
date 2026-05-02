<?php

namespace App\Domain\CreditNotes;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class CreditNoteFilterService
{
    use BaseFilterService;

    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyConfiguredFilters(
            $query,
            $filters,
            [
                'customer_id' => 'customer_id',
                'customer_invoice_id' => 'customer_invoice_id',
                'branch_id' => 'branch_id',
                'fiscal_year_id' => 'fiscal_year_id',
                'reason' => 'reason',
                'status' => 'status',
            ],
            [
                'credit_note_date' => ['from' => 'credit_note_date_from', 'to' => 'credit_note_date_to'],
            ],
            [
                'grand_total' => ['min' => 'grand_total_min', 'max' => 'grand_total_max'],
            ],
        );
    }
}
