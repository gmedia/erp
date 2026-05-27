<?php

namespace App\Actions\FiscalYears;

use App\Models\FiscalYear;
use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Collection;

class GetPreferredFiscalYearAction
{
    /**
     * @param  Collection<int, FiscalYear>  $fiscalYears  Collection ordered by start_date desc.
     */
    public function execute(Collection $fiscalYears): ?FiscalYear
    {
        if ($fiscalYears->isEmpty()) {
            return null;
        }

        $fiscalYearWithPostedEntries = $fiscalYears->first(
            fn (FiscalYear $fiscalYear): bool => JournalEntry::query()
                ->where('fiscal_year_id', $fiscalYear->id)
                ->where('status', 'posted')
                ->exists(),
        );

        if ($fiscalYearWithPostedEntries !== null) {
            return $fiscalYearWithPostedEntries;
        }

        return $fiscalYears->firstWhere('status', 'open') ?? $fiscalYears->first();
    }
}
