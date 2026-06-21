<?php

namespace App\Http\Controllers;

use App\Actions\ReportConfigurations\GetReportConfigurationByTypeAction;
use App\Actions\Reports\EvaluateReportSectionsAction;
use App\Actions\Reports\ExportBalanceSheetReportAction;
use App\Actions\Reports\ExportCashFlowReportAction;
use App\Actions\Reports\ExportComparativeReportAction;
use App\Actions\Reports\ExportIncomeStatementReportAction;
use App\Actions\Reports\ExportTrialBalanceFinancialReportAction;
use App\Http\Controllers\Concerns\InteractsWithFinancialReportRequest;
use App\Http\Requests\Reports\BalanceSheetReportRequest;
use App\Http\Requests\Reports\CashFlowReportRequest;
use App\Http\Requests\Reports\ComparativeReportRequest;
use App\Http\Requests\Reports\IncomeStatementReportRequest;
use App\Http\Requests\Reports\TrialBalanceFinancialReportRequest;
use App\Models\ReportConfiguration;
use App\Services\FinancialReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use InteractsWithFinancialReportRequest;

    public function __construct(
        protected FinancialReportService $reportService,
        protected GetReportConfigurationByTypeAction $configurationResolver,
        protected EvaluateReportSectionsAction $evaluateSections,
    ) {}

    public function trialBalance(Request $request): JsonResponse
    {
        [
            'fiscalYears' => $fiscalYears,
            'selectedYearId' => $selectedYearId,
        ] = $this->resolveFiscalYearContext($request);

        $report = [];
        $computedSections = [];
        if ($selectedYearId) {
            $branchId = $this->resolveBranchId($request);
            $report = $this->reportService->getTrialBalance($selectedYearId, $branchId);
            $config = $this->configurationResolver->execute(ReportConfiguration::TYPE_TRIAL_BALANCE);
            if ($config) {
                $computedSections = $this->evaluateSections->execute($config['sections'], $report['totals'] ?? []);
            }
        }

        return response()->json([
            'fiscalYears' => $fiscalYears,
            'selectedYearId' => (int) $selectedYearId,
            'report' => $report,
            'configuration' => $this->configurationResolver->execute(ReportConfiguration::TYPE_TRIAL_BALANCE),
            'computed_sections' => $computedSections,
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
        $computedSections = [];
        if ($selectedYearId) {
            $branchId = $this->resolveBranchId($request);
            $report = $this->reportService->getBalanceSheet(
                $selectedYearId,
                $comparisonYearId,
                $branchId,
            );
            $config = $this->configurationResolver->execute(ReportConfiguration::TYPE_BALANCE_SHEET);
            if ($config) {
                $computedSections = $this->evaluateSections->execute($config['sections'], $report['totals'] ?? []);
            }
        }

        return response()->json([
            'fiscalYears' => $fiscalYears,
            'selectedYearId' => (int) $selectedYearId,
            'comparisonYearId' => $comparisonYearId ? (int) $comparisonYearId : null,
            'report' => $report,
            'configuration' => $this->configurationResolver->execute(ReportConfiguration::TYPE_BALANCE_SHEET),
            'computed_sections' => $computedSections,
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
        $computedSections = [];
        if ($selectedYearId) {
            $branchId = $this->resolveBranchId($request);
            $report = $this->reportService->getIncomeStatement(
                $selectedYearId,
                $comparisonYearId,
                $branchId,
            );
            $config = $this->configurationResolver->execute(ReportConfiguration::TYPE_INCOME_STATEMENT);
            if ($config) {
                $computedSections = $this->evaluateSections->execute($config['sections'], $report['totals'] ?? []);
            }
        }

        return response()->json([
            'fiscalYears' => $fiscalYears,
            'selectedYearId' => (int) $selectedYearId,
            'comparisonYearId' => $comparisonYearId ? (int) $comparisonYearId : null,
            'report' => $report,
            'configuration' => $this->configurationResolver->execute(ReportConfiguration::TYPE_INCOME_STATEMENT),
            'computed_sections' => $computedSections,
        ]);
    }

    public function cashFlow(Request $request): JsonResponse
    {
        [
            'fiscalYears' => $fiscalYears,
            'selectedYearId' => $selectedYearId,
        ] = $this->resolveFiscalYearContext($request);

        $report = [];
        $computedSections = [];
        if ($selectedYearId) {
            $branchId = $this->resolveBranchId($request);
            $report = $this->reportService->getCashFlow($selectedYearId, $branchId);
            $config = $this->configurationResolver->execute(ReportConfiguration::TYPE_CASH_FLOW);
            if ($config) {
                $computedSections = $this->evaluateSections->execute($config['sections'], $report['totals'] ?? []);
            }
        }

        return response()->json([
            'fiscalYears' => $fiscalYears,
            'selectedYearId' => (int) $selectedYearId,
            'report' => $report,
            'configuration' => $this->configurationResolver->execute(ReportConfiguration::TYPE_CASH_FLOW),
            'computed_sections' => $computedSections,
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
            $branchId = $this->resolveBranchId($request);
            $report = $this->reportService->getComparativeReport(
                $selectedYearId,
                $comparisonYearId,
                $branchId,
            );
        }

        return response()->json([
            'fiscalYears' => $fiscalYears,
            'selectedYearId' => $selectedYearId,
            'comparisonYearId' => $comparisonYearId,
            'report' => $report,
        ]);
    }

    public function exportTrialBalance(
        TrialBalanceFinancialReportRequest $request,
        ExportTrialBalanceFinancialReportAction $action,
    ): JsonResponse {
        return $action->execute($request->validated());
    }

    public function exportBalanceSheet(
        BalanceSheetReportRequest $request,
        ExportBalanceSheetReportAction $action,
    ): JsonResponse {
        return $action->execute($request->validated());
    }

    public function exportIncomeStatement(
        IncomeStatementReportRequest $request,
        ExportIncomeStatementReportAction $action,
    ): JsonResponse {
        return $action->execute($request->validated());
    }

    public function exportCashFlow(CashFlowReportRequest $request, ExportCashFlowReportAction $action): JsonResponse
    {
        return $action->execute($request->validated());
    }

    public function exportComparative(
        ComparativeReportRequest $request,
        ExportComparativeReportAction $action,
    ): JsonResponse {
        return $action->execute($request->validated());
    }
}
