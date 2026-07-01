<?php

namespace App\Actions\Reports\Concerns;

use App\Actions\AgingDashboard\GetAgingDashboardDataAction;
use Illuminate\Support\Carbon;

/**
 * Computes Carbon-based aging bucket boundaries (today, 1, 30, 31, 60, 61, 90)
 * and renders cross-DB safe parameterized SQL fragments for AR/AP aging
 * + outstanding reports.
 *
 * Mirrors the pattern used in {@see GetAgingDashboardDataAction}:
 * date math is performed in PHP (UTC Carbon) and injected via prepared
 * statement bindings instead of MariaDB-only `CURDATE()` / `DATEDIFF()` calls.
 */
trait AgingReportBoundaries
{
    /**
     * @return array{today: string, d1: string, d30: string, d31: string, d60: string, d61: string, d90: string}
     */
    protected function agingBoundaries(): array
    {
        $today = Carbon::today();

        return [
            'today' => $today->toDateString(),
            'd1' => $today->copy()->subDays(1)->toDateString(),
            'd30' => $today->copy()->subDays(30)->toDateString(),
            'd31' => $today->copy()->subDays(31)->toDateString(),
            'd60' => $today->copy()->subDays(60)->toDateString(),
            'd61' => $today->copy()->subDays(61)->toDateString(),
            'd90' => $today->copy()->subDays(90)->toDateString(),
        ];
    }

    /**
     * Default AR-style aliases: aging_current, aging_1_30, aging_31_60, aging_61_90, aging_over_90.
     * Pair with {@see agingBucketBindings()} when calling selectRaw().
     */
    protected function agingBucketSelectSql(string $tableAlias): string
    {
        return $this->agingBucketSelectSqlWithAliases($tableAlias, [
            'current' => 'aging_current',
            '1_30' => 'aging_1_30',
            '31_60' => 'aging_31_60',
            '61_90' => 'aging_61_90',
            'over_90' => 'aging_over_90',
        ]);
    }

    /**
     * Renders the aging bucket CASE expressions with caller-provided column aliases
     * so consumers that need a different output shape (e.g. AP uses
     * `current_amount`, `days_1_30`, etc.) can share the same SQL logic without
     * forcing an API contract change. Pair with {@see agingBucketBindings()}.
     *
     * @param  array{current: string, '1_30': string, '31_60': string, '61_90': string, over_90: string}  $aliases
     */
    protected function agingBucketSelectSqlWithAliases(string $tableAlias, array $aliases): string
    {
        $aCurrent = $aliases['current'];
        $a1_30 = $aliases['1_30'];
        $a31_60 = $aliases['31_60'];
        $a61_90 = $aliases['61_90'];
        $aOver90 = $aliases['over_90'];

        return "CASE WHEN {$tableAlias}.due_date >= ? THEN {$tableAlias}.amount_due ELSE 0 END as {$aCurrent},
        CASE WHEN {$tableAlias}.due_date BETWEEN ? AND ? THEN {$tableAlias}.amount_due ELSE 0 END as {$a1_30},
        CASE WHEN {$tableAlias}.due_date BETWEEN ? AND ? THEN {$tableAlias}.amount_due ELSE 0 END as {$a31_60},
        CASE WHEN {$tableAlias}.due_date BETWEEN ? AND ? THEN {$tableAlias}.amount_due ELSE 0 END as {$a61_90},
        CASE WHEN {$tableAlias}.due_date < ? THEN {$tableAlias}.amount_due ELSE 0 END as {$aOver90}";
    }

    /**
     * @param  array{
     *     today: string, d1: string, d30: string, d31: string, d60: string, d61: string, d90: string
     * }  $boundaries
     * @return list<string>
     */
    protected function agingBucketBindings(array $boundaries): array
    {
        return [
            $boundaries['today'],
            $boundaries['d30'], $boundaries['d1'],
            $boundaries['d60'], $boundaries['d31'],
            $boundaries['d90'], $boundaries['d61'],
            $boundaries['d90'],
        ];
    }
}
