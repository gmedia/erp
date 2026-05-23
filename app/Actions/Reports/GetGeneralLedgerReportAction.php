<?php

namespace App\Actions\Reports;

use App\Models\JournalEntryLine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GetGeneralLedgerReportAction
{
    public function execute(array $filters): Collection
    {
        if (empty($filters['account_id'])) {
            return collect();
        }

        $running = 0.0;

        return JournalEntryLine::query()
            ->with(['account', 'journalEntry'])
            ->where('account_id', $filters['account_id'])
            ->whereHas('journalEntry', function (Builder $query) use ($filters): void {
                $query->where('status', 'posted');

                if (! empty($filters['fiscal_year_id'])) {
                    $query->where('fiscal_year_id', $filters['fiscal_year_id']);
                }

                if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
                    $query->whereBetween('entry_date', [$filters['start_date'], $filters['end_date']]);
                } elseif (! empty($filters['start_date'])) {
                    $query->where('entry_date', '>=', $filters['start_date']);
                } elseif (! empty($filters['end_date'])) {
                    $query->where('entry_date', '<=', $filters['end_date']);
                }

                if (! empty($filters['journal_type'])) {
                    $query->where('journal_type', $filters['journal_type']);
                }
            })
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entry_lines.id')
            ->select('journal_entry_lines.*')
            ->get()
            ->map(function (JournalEntryLine $line) use (&$running): array {
                $running += (float) $line->debit - (float) $line->credit;

                return [
                    'id' => $line->id,
                    'entry_number' => $line->journalEntry->entry_number,
                    'entry_date' => $line->journalEntry->entry_date->format('Y-m-d'),
                    'description' => $line->journalEntry->description,
                    'reference' => $line->journalEntry->reference,
                    'account_id' => $line->account_id,
                    'account_code' => $line->account->code,
                    'account_name' => $line->account->name,
                    'debit' => (float) $line->debit,
                    'credit' => (float) $line->credit,
                    'running_balance' => $running,
                    'memo' => $line->memo,
                ];
            });
    }
}
