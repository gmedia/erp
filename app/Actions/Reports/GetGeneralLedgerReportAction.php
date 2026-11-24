<?php

namespace App\Actions\Reports;

use App\Models\Account;
use App\Models\FiscalYear;
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

        $running = $this->openingBalance($filters);

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

    /**
     * Saldo awal = akumulasi debit-kredit jurnal posted sebelum start_date.
     * Rumus sama dengan trial balance: jurnal tahun fiskal yang dipilih, plus
     * untuk akun neraca (bukan revenue/expense) jurnal tahun sebelumnya.
     * Akun laba rugi hanya menghitung tahun berjalan.
     */
    private function openingBalance(array $filters): float
    {
        if (empty($filters['start_date'])) {
            return $this->carryForwardBalance($filters);
        }

        $fiscalYearId = $filters['fiscal_year_id'] ?? null;
        $accountType = Account::query()->whereKey($filters['account_id'])->value('type');
        $isPnl = in_array($accountType, ['revenue', 'expense'], true);

        $sum = JournalEntryLine::query()
            ->where('account_id', $filters['account_id'])
            ->whereHas('journalEntry', function (Builder $query) use ($filters, $fiscalYearId, $isPnl): void {
                $query->where('status', 'posted')
                    ->where('entry_date', '<', $filters['start_date']);

                // Neraca: semua jurnal sebelum start_date (termasuk tahun sebelumnya).
                // Laba rugi: hanya jurnal tahun fiskal yang dipilih.
                if (! empty($fiscalYearId) && $isPnl) {
                    $query->where('fiscal_year_id', $fiscalYearId);
                }

                if (! empty($filters['journal_type'])) {
                    $query->where('journal_type', $filters['journal_type']);
                }
            })
            ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) as net')
            ->value('net');

        return (float) $sum;
    }

    /**
     * Tanpa start_date: akun neraca membawa saldo jurnal sebelum awal tahun fiskal.
     */
    private function carryForwardBalance(array $filters): float
    {
        if (empty($filters['fiscal_year_id'])) {
            return 0.0;
        }

        $fiscalYearStart = FiscalYear::query()->whereKey($filters['fiscal_year_id'])->value('start_date');
        $accountType = Account::query()->whereKey($filters['account_id'])->value('type');

        if ($fiscalYearStart === null || in_array($accountType, ['revenue', 'expense'], true)) {
            return 0.0;
        }

        $sum = JournalEntryLine::query()
            ->where('account_id', $filters['account_id'])
            ->whereHas('journalEntry', function (Builder $query) use ($fiscalYearStart): void {
                $query->where('status', 'posted')
                    ->where('entry_date', '<', $fiscalYearStart);
            })
            ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) as net')
            ->value('net');

        return (float) $sum;
    }
}
