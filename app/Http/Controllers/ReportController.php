<?php

namespace App\Http\Controllers;

use App\Models\FiscalYear;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(
        protected FinancialReportService $reportService
    ) {}

    public function trialBalance(Request $request): JsonResponse
    {
        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')->get();
        $currentFiscalYear = $fiscalYears->firstWhere('status', 'open') ?? $fiscalYears->first();
        
        $selectedYearId = $request->input('fiscal_year_id', $currentFiscalYear?->id);

        $report = [];
        if ($selectedYearId) {
            $report = $this->reportService->getTrialBalance((int) $selectedYearId);
        }

        return response()->json([
            'fiscalYears' => $fiscalYears,
            'selectedYearId' => (int) $selectedYearId,
            'report' => $report,
        ]);
    }

    public function balanceSheet(Request $request): JsonResponse
    {
        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')->get();
        $currentFiscalYear = $fiscalYears->firstWhere('status', 'open') ?? $fiscalYears->first();
        
        $selectedYearId = $request->input('fiscal_year_id', $currentFiscalYear?->id);
        $comparisonYearId = $request->input('comparison_year_id');

        $report = [];
        if ($selectedYearId) {
            $report = $this->reportService->getBalanceSheet((int) $selectedYearId, $comparisonYearId ? (int) $comparisonYearId : null);
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
        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')->get();
        $currentFiscalYear = $fiscalYears->firstWhere('status', 'open') ?? $fiscalYears->first();

        $selectedYearId = $request->input('fiscal_year_id', $currentFiscalYear?->id);
        $comparisonYearId = $request->input('comparison_year_id');

        $report = [];
        if ($selectedYearId) {
            $report = $this->reportService->getIncomeStatement((int) $selectedYearId, $comparisonYearId ? (int) $comparisonYearId : null);
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
        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')->get();
        $currentFiscalYear = $fiscalYears->firstWhere('status', 'open') ?? $fiscalYears->first();

        $selectedYearId = $request->input('fiscal_year_id', $currentFiscalYear?->id);

        $report = [];
        if ($selectedYearId) {
            $report = $this->reportService->getCashFlow((int) $selectedYearId);
        }

        return response()->json([
            'fiscalYears' => $fiscalYears,
            'selectedYearId' => (int) $selectedYearId,
            'report' => $report,
        ]);
    }

    public function comparative(Request $request): JsonResponse
    {
        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')->get();
        $currentFiscalYear = $fiscalYears->firstWhere('status', 'open') ?? $fiscalYears->first();

        $selectedYearId = (int) $request->input('fiscal_year_id', $currentFiscalYear?->id);
        $defaultComparisonYearId = null;

        $selectedIndex = $fiscalYears->search(fn (FiscalYear $fy) => $fy->id === $selectedYearId);
        if (is_int($selectedIndex) && ($selectedIndex + 1) < $fiscalYears->count()) {
            $defaultComparisonYearId = $fiscalYears[$selectedIndex + 1]->id;
        }

        $comparisonYearId = $request->input('comparison_year_id', $defaultComparisonYearId);

        $report = [];
        if ($selectedYearId) {
            $report = $this->reportService->getComparativeReport($selectedYearId, $comparisonYearId ? (int) $comparisonYearId : null);
        }

        return response()->json([
            'fiscalYears' => $fiscalYears,
            'selectedYearId' => $selectedYearId,
            'comparisonYearId' => $comparisonYearId ? (int) $comparisonYearId : null,
            'report' => $report,
        ]);
    }
}
