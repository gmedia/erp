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
            $name = 'Recurring Journal Sample ' . ($index + 1);

            $journal = RecurringJournal::updateOrCreate(
                ['name' => $name, 'fiscal_year_id' => $fiscalYear->id],
                [
                    'frequency' => $index === 0 ? 'weekly' : 'monthly',
                    'next_run_date' => now()->startOfMonth()->addMonths($index),
                    'total_amount' => $amount,
                    'created_by' => $user->id,
                ],
            );

            $journal->lines()->delete();
            $journal->lines()->createMany([
                ['account_id' => $accounts[0]->id, 'debit' => $amount, 'credit' => 0, 'memo' => 'Debit sample line'],
                ['account_id' => $accounts[1]->id, 'debit' => 0, 'credit' => $amount, 'memo' => 'Credit sample line'],
            ]);
        }
    }

    private function seedBankReconciliations(FiscalYear $fiscalYear, Collection $accounts, User $user): void
    {
        foreach ([['in_progress', null], ['completed', now()]] as $index => [$status, $completedAt]) {
            $periodStart = now()->startOfMonth()->addMonths($index);
            $periodEnd = now()->startOfMonth()->addMonths($index)->endOfMonth();

            $reconciliation = BankReconciliation::updateOrCreate(
                [
                    'account_id' => $accounts[0]->id,
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
