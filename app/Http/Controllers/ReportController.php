<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithFinancialReportRequest;
use App\Services\FinancialReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use InteractsWithFinancialReportRequest;

    public function __construct(
        protected FinancialReportService $reportService
    ) {}

    public function trialBalance(Request $request): JsonResponse
    {
        ['fiscalYears' => $fiscalYears, 'selectedYearId' => $selectedYearId] = $this->resolveFiscalYearContext($request);

        $report = [];
        if ($selectedYearId) {
            $report = $this->reportService->getTrialBalance($selectedYearId);
        }

        return response()->json([
            'fiscalYears' => $fiscalYears,
            'selectedYearId' => (int) $selectedYearId,
            'report' => $report,
        ]);
    }

    public function balanceSheet(Request $request): JsonResponse
    {
        [
            'fiscalYears' => $fiscalYears,
            'selectedYearId' => $selectedYearId,
            'comparisonYearId' => $comparisonYearId,
        ] = $this->resolveFiscalYearContext($request, withComparison: true);

        $report = [];
        if ($selectedYearId) {
            $report = $this->reportService->getBalanceSheet(
                $selectedYearId,
                $comparisonYearId,
            );
        }

        return response()->json([
            'fiscalYears' => $fiscalYears,
            'selectedYearId' => (int) $selectedYearId,
            'comparisonYearId' => $comparisonYearId ? (int) $comparisonYearId : null,
            'report' => $report,
        ]);
    }

    public function incomeStatement(Request $request): JsonResponse
    {
        [
            'fiscalYears' => $fiscalYears,
            'selectedYearId' => $selectedYearId,
            'comparisonYearId' => $comparisonYearId,
        ] = $this->resolveFiscalYearContext($request, withComparison: true);

        $report = [];
        if ($selectedYearId) {
            $report = $this->reportService->getIncomeStatement(
                $selectedYearId,
                $comparisonYearId,
            );
        }

        return response()->json([
            'fiscalYears' => $fiscalYears,
            'selectedYearId' => (int) $selectedYearId,
            'comparisonYearId' => $comparisonYearId ? (int) $comparisonYearId : null,
            'report' => $report,
        ]);
    }

    public function cashFlow(Request $request): JsonResponse
    {
        ['fiscalYears' => $fiscalYears, 'selectedYearId' => $selectedYearId] = $this->resolveFiscalYearContext($request);

        $report = [];
        if ($selectedYearId) {
            $report = $this->reportService->getCashFlow($selectedYearId);
        }

        return response()->json([
            'fiscalYears' => $fiscalYears,
            'selectedYearId' => (int) $selectedYearId,
            'report' => $report,
        ]);
    }

    public function comparative(Request $request): JsonResponse
    {
        [
            'fiscalYears' => $fiscalYears,
            'selectedYearId' => $selectedYearId,
            'comparisonYearId' => $comparisonYearId,
        ] = $this->resolveFiscalYearContext(
            $request,
            withComparison: true,
            usePreviousComparisonDefault: true,
        );

        $report = [];
        if ($selectedYearId) {
            $report = $this->reportService->getComparativeReport(
                $selectedYearId,
                $comparisonYearId,
            );
        }

        return response()->json([
            'fiscalYears' => $fiscalYears,
            'selectedYearId' => $selectedYearId,
            'comparisonYearId' => $comparisonYearId,
            'report' => $report,
        ]);
    }
}
