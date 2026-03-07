<?php

namespace App\Http\Controllers;

use App\Models\FiscalYear;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(
        protected FinancialReportService $reportService
    ) {}

    public function trialBalance(Request $request): Response|JsonResponse
    {
        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')->get();
        // Default to latest open fiscal year or just latest
        $currentFiscalYear = $fiscalYears->firstWhere('status', 'open') ?? $fiscalYears->first();
        
        $selectedYearId = $request->input('fiscal_year_id', $currentFiscalYear?->id);

        $report = [];
        if ($selectedYearId) {
            $report = $this->reportService->getTrialBalance((int) $selectedYearId);
        }

        $data = [
            'fiscalYears' => $fiscalYears,
            'selectedYearId' => (int) $selectedYearId,
            'report' => $report,
        ];

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($data);
        }

        return Inertia::render('reports/trial-balance/index', $data);
    }

    public function balanceSheet(Request $request): Response|JsonResponse
    {
        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')->get();
        $currentFiscalYear = $fiscalYears->firstWhere('status', 'open') ?? $fiscalYears->first();
        
        $selectedYearId = $request->input('fiscal_year_id', $currentFiscalYear?->id);
        $comparisonYearId = $request->input('comparison_year_id');

        $report = [];
        if ($selectedYearId) {
            $report = $this->reportService->getBalanceSheet((int) $selectedYearId, $comparisonYearId ? (int) $comparisonYearId : null);
        }

        $data = [
            'fiscalYears' => $fiscalYears,
            'selectedYearId' => (int) $selectedYearId,
            'comparisonYearId' => $comparisonYearId ? (int) $comparisonYearId : null,
            'report' => $report,
        ];

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($data);
        }

        return Inertia::render('reports/balance-sheet/index', $data);
    }

    public function incomeStatement(Request $request): Response|JsonResponse
    {
        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')->get();
        $currentFiscalYear = $fiscalYears->firstWhere('status', 'open') ?? $fiscalYears->first();

        $selectedYearId = $request->input('fiscal_year_id', $currentFiscalYear?->id);
        $comparisonYearId = $request->input('comparison_year_id');

        $report = [];
        if ($selectedYearId) {
            $report = $this->reportService->getIncomeStatement((int) $selectedYearId, $comparisonYearId ? (int) $comparisonYearId : null);
        }

        $data = [
            'fiscalYears' => $fiscalYears,
            'selectedYearId' => (int) $selectedYearId,
            'comparisonYearId' => $comparisonYearId ? (int) $comparisonYearId : null,
            'report' => $report,
        ];

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($data);
        }

        return Inertia::render('reports/income-statement/index', $data);
    }

    public function cashFlow(Request $request): Response|JsonResponse
    {
        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')->get();
        $currentFiscalYear = $fiscalYears->firstWhere('status', 'open') ?? $fiscalYears->first();

        $selectedYearId = $request->input('fiscal_year_id', $currentFiscalYear?->id);

        $report = [];
        if ($selectedYearId) {
            $report = $this->reportService->getCashFlow((int) $selectedYearId);
        }

        $data = [
            'fiscalYears' => $fiscalYears,
            'selectedYearId' => (int) $selectedYearId,
            'report' => $report,
        ];

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($data);
        }

        return Inertia::render('reports/cash-flow/index', $data);
    }

    public function comparative(Request $request): Response|JsonResponse
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

        $data = [
            'fiscalYears' => $fiscalYears,
            'selectedYearId' => $selectedYearId,
            'comparisonYearId' => $comparisonYearId ? (int) $comparisonYearId : null,
            'report' => $report,
        ];

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($data);
        }

        return Inertia::render('reports/comparative/index', $data);
    }
}
