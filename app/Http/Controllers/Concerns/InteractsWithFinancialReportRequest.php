<?php

namespace App\Http\Controllers\Concerns;

use App\Models\FiscalYear;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

trait InteractsWithFinancialReportRequest
{
    /**
     * @return array{fiscalYears: Collection<int, FiscalYear>, selectedYearId: ?int, comparisonYearId: ?int}
     */
    protected function resolveFiscalYearContext(
        Request $request,
        bool $withComparison = false,
        bool $usePreviousComparisonDefault = false,
    ): array {
        /** @var Collection<int, FiscalYear> $fiscalYears */
        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')->get();
        $currentFiscalYear = $fiscalYears->firstWhere('status', 'open') ?? $fiscalYears->first();
        $selectedYearId = $this->resolveSelectedYearId($request, $currentFiscalYear?->id);

        if (! $withComparison) {
            return [
                'fiscalYears' => $fiscalYears,
                'selectedYearId' => $selectedYearId,
                'comparisonYearId' => null,
            ];
        }

        $comparisonYearId = $this->resolveComparisonYearId(
            $request,
            $fiscalYears,
            $selectedYearId,
            $usePreviousComparisonDefault,
        );

        return [
            'fiscalYears' => $fiscalYears,
            'selectedYearId' => $selectedYearId,
            'comparisonYearId' => $comparisonYearId,
        ];
    }

    protected function resolveSelectedYearId(Request $request, ?int $defaultFiscalYearId): ?int
    {
        $selectedYearId = $request->input('fiscal_year_id', $defaultFiscalYearId);

        if ($selectedYearId === null) {
            return null;
        }

        return (int) $selectedYearId;
    }

    /**
     * @param  Collection<int, FiscalYear>  $fiscalYears
     */
    protected function resolveComparisonYearId(
        Request $request,
        Collection $fiscalYears,
        ?int $selectedYearId,
        bool $usePreviousComparisonDefault,
    ): ?int {
        $defaultComparisonYearId = $usePreviousComparisonDefault
            ? $this->resolvePreviousFiscalYearId($fiscalYears, $selectedYearId)
            : null;

        $comparisonYearId = $request->input('comparison_year_id', $defaultComparisonYearId);

        if ($comparisonYearId === null) {
            return null;
        }

        return (int) $comparisonYearId;
    }

    /**
     * @param  Collection<int, FiscalYear>  $fiscalYears
     */
    protected function resolvePreviousFiscalYearId(Collection $fiscalYears, ?int $selectedYearId): ?int
    {
        if ($selectedYearId === null) {
            return null;
        }

        $selectedIndex = $fiscalYears->search(fn (FiscalYear $fiscalYear) => $fiscalYear->id === $selectedYearId);

        if (! is_int($selectedIndex) || ($selectedIndex + 1) >= $fiscalYears->count()) {
            return null;
        }

        return $fiscalYears[$selectedIndex + 1]->id;
    }
}
