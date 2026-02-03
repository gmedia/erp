<?php

namespace App\Http\Controllers;

use App\Models\FiscalYear;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(
        protected FinancialReportService $reportService
    ) {}

    public function trialBalance(Request $request): Response
    {
        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')->get();
        // Default to latest open fiscal year or just latest
        $currentFiscalYear = $fiscalYears->firstWhere('status', 'open') ?? $fiscalYears->first();
        
        $selectedYearId = $request->input('fiscal_year_id', $currentFiscalYear?->id);

        $report = [];
        if ($selectedYearId) {
            $report = $this->reportService->getTrialBalance((int) $selectedYearId);
        }

        return Inertia::render('reports/trial-balance/index', [
            'fiscalYears' => $fiscalYears,
            'selectedYearId' => (int) $selectedYearId,
            'report' => $report,
        ]);
    }

    public function balanceSheet(Request $request): Response
    {
        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')->get();
        $currentFiscalYear = $fiscalYears->firstWhere('status', 'open') ?? $fiscalYears->first();
        
        $selectedYearId = $request->input('fiscal_year_id', $currentFiscalYear?->id);

        $report = [];
        if ($selectedYearId) {
            $report = $this->reportService->getBalanceSheet((int) $selectedYearId);
        }

        return Inertia::render('reports/balance-sheet/index', [
            'fiscalYears' => $fiscalYears,
            'selectedYearId' => (int) $selectedYearId,
            'report' => $report,
        ]);
    }
}
