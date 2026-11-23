<?php

namespace App\Actions\Reports\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ringkasan laporan (total + tanggal As of) yang dihitung di database untuk
 * SELURUH hasil filter, bukan hanya satu halaman pagination (W7-13b).
 *
 * Query terfilter dibungkus sebagai subquery lalu di-SUM, sehingga alias kolom
 * hasil (mis. aging_current) bisa dijumlahkan tanpa mengulang ekspresi CASE
 * beserta binding-nya. ORDER BY dihapus agar tidak ikut masuk subquery.
 */
trait SumsReportTotals
{
    /**
     * @param  array<int, string>  $columns  alias kolom pada hasil query yang akan dijumlahkan
     * @return array{as_of_date: string, totals: array<string, string>}
     */
    protected function buildReportSummary(Builder $query, array $columns): array
    {
        $inner = (clone $query)->reorder()->toBase();

        $select = array_map(
            static fn (string $column): string => 'COALESCE(SUM(t.' . $column . '), 0) as ' . $column,
            $columns,
        );

        $row = DB::query()
            ->fromSub($inner, 't')
            ->selectRaw(implode(', ', $select))
            ->first();

        $totals = [];

        foreach ($columns as $column) {
            $totals[$column] = number_format((float) ($row->{$column} ?? 0), 2, '.', '');
        }

        return [
            'as_of_date' => Carbon::today()->toDateString(),
            'totals' => $totals,
        ];
    }
}
