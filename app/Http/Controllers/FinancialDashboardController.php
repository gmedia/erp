<?php

namespace App\Http\Controllers;

use App\Actions\FinancialDashboard\GetFinancialDashboardDataAction;
use App\Http\Controllers\Concerns\InteractsWithFinancialReportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialDashboardController extends Controller
{
    use InteractsWithFinancialReportRequest;

    public function __invoke(
        Request $request,
        GetFinancialDashboardDataAction $action,
    ): JsonResponse {
        [
            'fiscalYears' => $fiscalYears,
            'selectedYearId' => $selectedYearId,
            'comparisonYearId' => $comparisonYearId,
        ] = $this->resolveFiscalYearContext($request, withComparison: true, usePreviousComparisonDefault: true);

        $data = [];
        if ($selectedYearId) {
            $data = $action->execute($selectedYearId, $comparisonYearId);
        }

        return response()->json([
            'fiscal_years' => $fiscalYears,
            'selected_year_id' => $selectedYearId,
            'comparison_year_id' => $comparisonYearId,
            ...$data,
        ]);
    }
}
