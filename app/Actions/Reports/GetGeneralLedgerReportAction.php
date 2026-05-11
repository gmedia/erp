<?php

namespace App\Actions\Reports;

use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;

class GetGeneralLedgerReportAction
{
    public function execute(array $filters): Collection
    {
        $running = 0.0;

        return JournalEntryLine::query()
            ->with(['account', 'journalEntry'])
            ->where('account_id', $filters['account_id'])
            ->whereHas('journalEntry', fn ($query) => $query
                ->where('fiscal_year_id', $filters['fiscal_year_id'])
                ->where('status', 'posted')
                ->whereBetween('entry_date', [$filters['start_date'], $filters['end_date']]))
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
