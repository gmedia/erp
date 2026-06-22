<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\FiscalYear;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BudgetSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $adminUserId = User::query()->where('email', config('app.admin'))->value('id') ?? User::query()->value('id');
        $fiscalYear = FiscalYear::query()->where('status', 'open')->first();

        if (! $adminUserId || ! $fiscalYear) {
            return;
        }

        $expenseAccounts = Account::query()
            ->where('type', 'expense')
            ->where('is_active', true)
            ->orderBy('code')
            ->take(5)
            ->get();

        $assetAccounts = Account::query()
            ->where('type', 'asset')
            ->where('is_active', true)
            ->orderBy('code')
            ->take(5)
            ->get();

        $this->seedBudgets($adminUserId, $fiscalYear, $expenseAccounts, $assetAccounts);
    }

    private function seedBudgets(
        int $adminUserId,
        FiscalYear $fiscalYear,
        $expenseAccounts,
        $assetAccounts
    ): void {
        $fyName = $fiscalYear->name;

        $budgetDefinitions = [
            [
                'name' => "Operational Budget {$fyName} (Draft)",
                'description' => 'Sample draft operational budget for testing Budget module.',
                'budget_type' => 'operational',
                'status' => 'draft',
                'approved' => false,
                'accounts' => $expenseAccounts,
                'lines' => [
                    ['account_offset' => 0, 'amount' => 50000000, 'period_start' => '2026-01-01', 'period_end' => '2026-01-31'],
                    ['account_offset' => 1, 'amount' => 35000000, 'period_start' => '2026-01-01', 'period_end' => '2026-01-31'],
                    ['account_offset' => 2, 'amount' => 25000000, 'period_start' => '2026-01-01', 'period_end' => '2026-01-31'],
                    ['account_offset' => 0, 'amount' => 45000000, 'period_start' => '2026-02-01', 'period_end' => '2026-02-28'],
                    ['account_offset' => 1, 'amount' => 30000000, 'period_start' => '2026-02-01', 'period_end' => '2026-02-28'],
                ],
            ],
            [
                'name' => "Operational Budget {$fyName} (Approved)",
                'description' => 'Sample approved operational budget for testing Budget module.',
                'budget_type' => 'operational',
                'status' => 'approved',
                'approved' => true,
                'accounts' => $expenseAccounts,
                'lines' => [
                    ['account_offset' => 0, 'amount' => 80000000, 'period_start' => '2026-03-01', 'period_end' => '2026-03-31'],
                    ['account_offset' => 1, 'amount' => 55000000, 'period_start' => '2026-03-01', 'period_end' => '2026-03-31'],
                    ['account_offset' => 2, 'amount' => 40000000, 'period_start' => '2026-03-01', 'period_end' => '2026-03-31'],
                    ['account_offset' => 3, 'amount' => 30000000, 'period_start' => '2026-04-01', 'period_end' => '2026-04-30'],
                    ['account_offset' => 4, 'amount' => 20000000, 'period_start' => '2026-04-01', 'period_end' => '2026-04-30'],
                    ['account_offset' => 0, 'amount' => 60000000, 'period_start' => '2026-05-01', 'period_end' => '2026-05-31'],
                ],
            ],
            [
                'name' => "Capital Budget {$fyName} (Locked)",
                'description' => 'Sample locked capital budget for testing Budget module.',
                'budget_type' => 'capital',
                'status' => 'locked',
                'approved' => true,
                'accounts' => $assetAccounts,
                'lines' => [
                    ['account_offset' => 0, 'amount' => 150000000, 'period_start' => '2026-01-01', 'period_end' => '2026-06-30'],
                    ['account_offset' => 1, 'amount' => 200000000, 'period_start' => '2026-01-01', 'period_end' => '2026-06-30'],
                    ['account_offset' => 2, 'amount' => 120000000, 'period_start' => '2026-07-01', 'period_end' => '2026-12-31'],
                    ['account_offset' => 3, 'amount' => 180000000, 'period_start' => '2026-07-01', 'period_end' => '2026-12-31'],
                ],
            ],
        ];

        foreach ($budgetDefinitions as $definition) {
            $totalAmount = 0;
            foreach ($definition['lines'] as $line) {
                $totalAmount += $line['amount'];
            }

            $budget = Budget::create([
                'ulid' => Str::ulid()->toBase32(),
                'fiscal_year_id' => $fiscalYear->id,
                'name' => $definition['name'],
                'description' => $definition['description'],
                'budget_type' => $definition['budget_type'],
                'status' => $definition['status'],
                'total_amount' => $totalAmount,
                'created_by' => $adminUserId,
                'approved_by' => $definition['approved'] ? $adminUserId : null,
                'approved_at' => $definition['approved'] ? now() : null,
            ]);

            foreach ($definition['lines'] as $line) {
                $account = $definition['accounts'][$line['account_offset']] ?? null;
                if (! $account) {
                    continue;
                }

                BudgetLine::create([
                    'budget_id' => $budget->id,
                    'account_id' => $account->id,
                    'period_start' => $line['period_start'],
                    'period_end' => $line['period_end'],
                    'allocated_amount' => $line['amount'],
                    'notes' => 'Sample budget line for testing.',
                ]);
            }
        }
    }
}
