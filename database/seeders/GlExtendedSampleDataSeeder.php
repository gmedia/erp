<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\BankReconciliation;
use App\Models\FiscalYear;
use App\Models\PeriodClosing;
use App\Models\RecurringJournal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GlExtendedSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->first() ?? User::factory()->create();
        $fiscalYear = FiscalYear::where('status', 'open')->first() ?? FiscalYear::factory()->create([
            'name' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
        ]);
        $accounts = $this->accounts();

        DB::transaction(function () use ($fiscalYear, $accounts, $user): void {
            $this->seedRecurringJournals($fiscalYear, $accounts, $user);
            $this->seedBankReconciliations($fiscalYear, $accounts, $user);
            $this->seedPeriodClosings($fiscalYear, $accounts, $user);
            $this->seedAccountBalances($fiscalYear, $accounts);
        });
    }

    /**
     * @return Collection<int, Account>
     */
    private function accounts(): Collection
    {
        $accounts = Account::query()->take(5)->get();

        if ($accounts->count() >= 5) {
            return $accounts;
        }

        return $accounts->merge(Account::factory()->count(5 - $accounts->count())->create());
    }

    private function seedRecurringJournals(FiscalYear $fiscalYear, Collection $accounts, User $user): void
    {
        foreach ([750000, 1250000, 2500000, 3750000] as $index => $amount) {
            $name = 'Jurnal Berulang Beban Operasional ' . ($index + 1);
            $legacyName = 'Recurring Journal Sample ' . ($index + 1);

            $legacyJournals = RecurringJournal::where('name', $legacyName)
                ->where('fiscal_year_id', $fiscalYear->id)
                ->get();

            if (RecurringJournal::where('name', $name)->where('fiscal_year_id', $fiscalYear->id)->exists()) {
                foreach ($legacyJournals as $legacyJournal) {
                    $legacyJournal->lines()->delete();
                    $legacyJournal->delete();
                }
            } elseif ($legacyJournals->isNotEmpty()) {
                $legacyJournals->first()->update(['name' => $name]);
            }

            $journal = RecurringJournal::updateOrCreate(
                ['name' => $name, 'fiscal_year_id' => $fiscalYear->id],
                [
                    'description' => $name . ' - {MM}/{YYYY}',
                    'reference_template' => 'RJ-{YYYY}-{MM}-{DD}-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'frequency' => $index === 0 ? 'weekly' : 'monthly',
                    'next_run_date' => now()->startOfMonth()->addMonths($index),
                    'total_amount' => $amount,
                    'created_by' => $user->id,
                ],
            );

            $journal->lines()->delete();
            $journal->lines()->createMany([
                ['account_id' => $accounts[0]->id, 'debit' => $amount, 'credit' => 0, 'memo' => 'Beban operasional bulanan'],
                ['account_id' => $accounts[1]->id, 'debit' => 0, 'credit' => $amount, 'memo' => 'Kas keluar untuk beban operasional'],
            ]);
        }
    }

    private function seedBankReconciliations(FiscalYear $fiscalYear, Collection $accounts, User $user): void
    {
        // Akun rekonsiliasi harus akun bank leaf, bukan akun induk (W5-21).
        $bankAccount = Account::query()
            ->where('code', '11110')
            ->first() ?? $accounts[0];

        // Tidak ada periode masa depan (W5-21): bulan berjalan + bulan lalu.
        foreach ([['in_progress', null], ['completed', now()]] as $index => [$status, $completedAt]) {
            $periodStart = now()->startOfMonth()->subMonths(1 - $index);
            $periodEnd = now()->startOfMonth()->subMonths(1 - $index)->endOfMonth();

            $reconciliation = BankReconciliation::updateOrCreate(
                [
                    'account_id' => $bankAccount->id,
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                ],
                [
                    'fiscal_year_id' => $fiscalYear->id,
                    'reconciliation_date' => $periodEnd->toDateString(),
                    'statement_balance' => 10000000 + ($index * 500000),
                    'book_balance' => 10000000 + ($index * 500000),
                    'reconciled_balance' => 10000000 + ($index * 500000),
                    'difference' => 0,
                    'status' => $status,
                    'completed_by' => $completedAt ? $user->id : null,
                    'completed_at' => $completedAt,
                    'created_by' => $user->id,
                ],
            );

            $reconciliation->items()->delete();
            $reconciliation->items()->createMany([
                ['transaction_date' => $periodStart->toDateString(), 'description' => 'Bank fee', 'debit' => 0, 'credit' => 25000, 'type' => 'bank_fee', 'is_reconciled' => true],
                ['transaction_date' => $periodEnd->toDateString(), 'description' => 'Interest income', 'debit' => 25000, 'credit' => 0, 'type' => 'interest', 'is_reconciled' => $status === 'completed'],
            ]);
        }
    }

    private function seedPeriodClosings(FiscalYear $fiscalYear, Collection $accounts, User $user): void
    {
        $periodYear = (int) $fiscalYear->start_date->format('Y');

        foreach ([1, 2] as $month) {
            PeriodClosing::updateOrCreate(
                [
                    'fiscal_year_id' => $fiscalYear->id,
                    'period_month' => $month,
                    'period_year' => $periodYear,
                    'closing_type' => 'monthly',
                ],
                [
                    'status' => 'closed',
                    'net_income' => $this->monthlyNetIncome(
                        $fiscalYear,
                        $month,
                        $periodYear,
                    ),
                    'retained_earnings_account_id' => $accounts[2]->id,
                    'closed_by' => $user->id,
                    'closed_at' => now(),
                    'created_by' => $user->id,
                ],
            );
        }

        PeriodClosing::updateOrCreate(
            [
                'fiscal_year_id' => $fiscalYear->id,
                'period_month' => 3,
                'period_year' => $periodYear,
                'closing_type' => 'monthly',
            ],
            [
                'status' => 'draft',
                'retained_earnings_account_id' => $accounts[2]->id,
                'created_by' => $user->id,
            ],
        );
    }

    /**
     * Laba bersih bulanan dari jurnal posted (revenue - expense), agar
     * period closing hasil seed tidak menampilkan Rp 0 (W5-16).
     */
    private function monthlyNetIncome(
        FiscalYear $fiscalYear,
        int $month,
        int $year,
    ): float {
        $row = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.fiscal_year_id', $fiscalYear->id)
            ->where('journal_entries.status', 'posted')
            ->where('journal_entries.journal_type', '<>', 'closing')
            ->whereIn('accounts.type', ['revenue', 'expense'])
            ->whereYear('journal_entries.entry_date', $year)
            ->whereMonth('journal_entries.entry_date', $month)
            // revenue normal kredit, expense normal debit → credit - debit
            // sudah merupakan laba bersih.
            ->selectRaw(
                'COALESCE(SUM(journal_entry_lines.credit - journal_entry_lines.debit), 0) as net',
            )
            ->first();

        return round((float) ($row->net ?? 0), 2);
    }

    private function seedAccountBalances(FiscalYear $fiscalYear, Collection $accounts): void
    {
        $periodYear = (int) $fiscalYear->start_date->format('Y');

        foreach ($accounts->take(4) as $account) {
            foreach ([1, 2, 3] as $month) {
                AccountBalance::updateOrCreate(
                    [
                        'account_id' => $account->id,
                        'fiscal_year_id' => $fiscalYear->id,
                        'period_month' => $month,
                        'period_year' => $periodYear,
                    ],
                    [
                        'opening_balance' => 1000000 * $month,
                        'debit_total' => 250000 * $month,
                        'credit_total' => 125000 * $month,
                        'movement' => 125000 * $month,
                        'closing_balance' => (1000000 * $month) + (125000 * $month),
                        'last_recalculated_at' => now(),
                    ],
                );
            }
        }
    }
}
