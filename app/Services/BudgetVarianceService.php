<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetLine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BudgetVarianceService
{
    /**
     * Calculate variance for all lines in a budget.
     */
    public function calculateVariance(Budget $budget): Collection
    {
        $budget->loadMissing('lines.account');

        return $budget->lines->map(function (BudgetLine $line) {
            $actual = $this->getActualForAccountPeriod(
                $line->account_id,
                $line->period_start,
                $line->period_end,
                $line->budget->fiscal_year_id,
                $line->account->type,
            );

            $allocated = (float) $line->allocated_amount;
            $available = $allocated - $actual;
            $variancePercent = $allocated > 0
                ? round((($allocated - $actual) / $allocated) * 100, 2)
                : null;

            // Status: within_budget, warning (>80% spent), over_budget
            $status = 'within_budget';
            if ($actual > $allocated) {
                $status = 'over_budget';
            } elseif ($allocated > 0 && ($actual / $allocated) >= 0.8) {
                $status = 'warning';
            }

            return [
                'account_id' => $line->account_id,
                'account_code' => $line->account->code,
                'account_name' => $line->account->name,
                'account_type' => $line->account->type,
                'period_start' => $line->period_start->format('Y-m-d'),
                'period_end' => $line->period_end->format('Y-m-d'),
                'allocated' => $allocated,
                'actual' => $actual,
                'available' => $available,
                'variance_percent' => $variancePercent,
                'status' => $status,
            ];
        });
    }

    /**
     * Get summary totals for a budget variance.
     *
     * @return array{
     *     total_allocated: float,
     *     total_actual: float,
     *     total_available: float,
     *     overall_variance_percent: float|null,
     * }
     */
    public function calculateSummary(Collection $varianceData): array
    {
        return [
            'total_allocated' => $varianceData->sum('allocated'),
            'total_actual' => $varianceData->sum('actual'),
            'total_available' => $varianceData->sum('available'),
            'overall_variance_percent' => $varianceData->sum('allocated') > 0
                ? round(
                    ($varianceData->sum('allocated') - $varianceData->sum('actual'))
                    / $varianceData->sum('allocated')
                    * 100,
                    2,
                )
                : null,
        ];
    }

    /**
     * Get actual posted amount for an account within a date range.
     * Sign-aware: expense/asset = debit - credit; revenue/liability/equity = credit - debit.
     */
    private function getActualForAccountPeriod(
        int $accountId,
        Carbon $periodStart,
        Carbon $periodEnd,
        int $fiscalYearId,
        string $accountType,
    ): float {
        $result = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entry_lines.account_id', $accountId)
            ->where('journal_entries.fiscal_year_id', $fiscalYearId)
            ->where('journal_entries.status', 'posted')
            ->whereBetween('journal_entries.entry_date', [$periodStart->format('Y-m-d'), $periodEnd->format('Y-m-d')])
            ->selectRaw(
                'COALESCE(SUM(journal_entry_lines.debit), 0) as total_debit, ' .
                'COALESCE(SUM(journal_entry_lines.credit), 0) as total_credit',
            )
            ->first();

        $totalDebit = (float) $result->total_debit;
        $totalCredit = (float) $result->total_credit;

        // Sign logic: expense/asset accounts = debit positive; revenue/liability/equity = credit positive
        return in_array($accountType, ['expense', 'asset'], true)
            ? $totalDebit - $totalCredit
            : $totalCredit - $totalDebit;
    }
}
