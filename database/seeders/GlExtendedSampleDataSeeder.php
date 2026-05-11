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

class GlExtendedSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->first() ?? User::factory()->create();
        $fiscalYear = FiscalYear::query()->first() ?? FiscalYear::factory()->create([
            'name' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
        ]);
        $accounts = $this->accounts();

        $this->seedRecurringJournals($fiscalYear, $accounts, $user);
        $this->seedBankReconciliations($fiscalYear, $accounts, $user);
        $this->seedPeriodClosings($fiscalYear, $accounts, $user);
        $this->seedAccountBalances($fiscalYear, $accounts);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Account>
     */
    private function accounts(): \Illuminate\Support\Collection
    {
        $accounts = Account::query()->take(5)->get();

        if ($accounts->count() >= 5) {
            return $accounts;
        }

        return $accounts->merge(Account::factory()->count(5 - $accounts->count())->create());
    }

    private function seedRecurringJournals(FiscalYear $fiscalYear, \Illuminate\Support\Collection $accounts, User $user): void
    {
        foreach ([750000, 1250000, 2500000, 3750000] as $index => $amount) {
            $journal = RecurringJournal::factory()->create([
                'name' => 'Recurring Journal Sample ' . ($index + 1),
                'fiscal_year_id' => $fiscalYear->id,
                'frequency' => $index === 0 ? 'weekly' : 'monthly',
                'next_run_date' => now()->startOfMonth()->addMonths($index),
                'total_amount' => $amount,
                'created_by' => $user->id,
            ]);

            $journal->lines()->createMany([
                ['account_id' => $accounts[0]->id, 'debit' => $amount, 'credit' => 0, 'memo' => 'Debit sample line'],
                ['account_id' => $accounts[1]->id, 'debit' => 0, 'credit' => $amount, 'memo' => 'Credit sample line'],
            ]);
        }
    }

    private function seedBankReconciliations(FiscalYear $fiscalYear, \Illuminate\Support\Collection $accounts, User $user): void
    {
        foreach ([['in_progress', null], ['completed', now()]] as $index => [$status, $completedAt]) {
            $reconciliation = BankReconciliation::factory()->create([
                'account_id' => $accounts[0]->id,
                'fiscal_year_id' => $fiscalYear->id,
                'reconciliation_date' => now()->startOfMonth()->addMonths($index)->endOfMonth(),
                'period_start' => now()->startOfMonth()->addMonths($index),
                'period_end' => now()->startOfMonth()->addMonths($index)->endOfMonth(),
                'statement_balance' => 10000000 + ($index * 500000),
                'book_balance' => 10000000 + ($index * 500000),
                'reconciled_balance' => 10000000 + ($index * 500000),
                'difference' => 0,
                'status' => $status,
                'completed_by' => $completedAt ? $user->id : null,
                'completed_at' => $completedAt,
                'created_by' => $user->id,
            ]);

            $reconciliation->items()->createMany([
                ['transaction_date' => $reconciliation->period_start, 'description' => 'Bank fee', 'debit' => 0, 'credit' => 25000, 'type' => 'bank_fee', 'is_reconciled' => true],
                ['transaction_date' => $reconciliation->period_end, 'description' => 'Interest income', 'debit' => 25000, 'credit' => 0, 'type' => 'interest', 'is_reconciled' => true],
            ]);
        }
    }

    private function seedPeriodClosings(FiscalYear $fiscalYear, \Illuminate\Support\Collection $accounts, User $user): void
    {
        foreach ([1, 2] as $month) {
            PeriodClosing::factory()->closed()->create([
                'fiscal_year_id' => $fiscalYear->id,
                'period_month' => $month,
                'period_year' => (int) $fiscalYear->start_date->format('Y'),
                'closing_type' => 'monthly',
                'retained_earnings_account_id' => $accounts[2]->id,
                'closed_by' => $user->id,
                'created_by' => $user->id,
            ]);
        }

        PeriodClosing::factory()->create([
            'fiscal_year_id' => $fiscalYear->id,
            'period_month' => 3,
            'period_year' => (int) $fiscalYear->start_date->format('Y'),
            'closing_type' => 'monthly',
            'status' => 'draft',
            'retained_earnings_account_id' => $accounts[2]->id,
            'created_by' => $user->id,
        ]);
    }

    private function seedAccountBalances(FiscalYear $fiscalYear, \Illuminate\Support\Collection $accounts): void
    {
        foreach ($accounts->take(4) as $account) {
            foreach ([1, 2, 3] as $month) {
                AccountBalance::factory()->create([
                    'account_id' => $account->id,
                    'fiscal_year_id' => $fiscalYear->id,
                    'period_month' => $month,
                    'period_year' => (int) $fiscalYear->start_date->format('Y'),
                    'opening_balance' => 1000000 * $month,
                    'debit_total' => 250000 * $month,
                    'credit_total' => 125000 * $month,
                    'movement' => 125000 * $month,
                    'closing_balance' => (1000000 * $month) + (125000 * $month),
                    'last_recalculated_at' => now(),
                ]);
            }
        }
    }
}
