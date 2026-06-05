<?php

namespace App\Http\Controllers;

use App\Exports\BudgetVarianceExport;
use App\Http\Requests\Budgets\BudgetVarianceReportRequest;
use App\Models\Budget;
use App\Services\BudgetVarianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class BudgetVarianceReportController extends Controller
{
    public function __construct(
        private BudgetVarianceService $varianceService,
    ) {}

    public function index(BudgetVarianceReportRequest $request): JsonResponse
    {
        $budget = Budget::with('lines.account')->findOrFail($request->validated('budget_id'));

        $varianceData = $this->varianceService->calculateVariance($budget);
        $summary = $this->varianceService->calculateSummary($varianceData);

        if ($request->filled('status')) {
            $varianceData = $varianceData->where('status', $request->validated('status'))->values();
        }

        if ($request->filled('account_type')) {
            $varianceData = $varianceData->where('account_type', $request->validated('account_type'))->values();
        }

        return response()->json([
            'data' => $varianceData->values()->all(),
            'summary' => $summary,
            'meta' => [
                'budget_id' => $budget->id,
                'budget_name' => $budget->name,
                'fiscal_year_id' => $budget->fiscal_year_id,
            ],
        ]);
    }

    public function export(BudgetVarianceReportRequest $request): JsonResponse
    {
        $budget = Budget::with('lines.account')->findOrFail($request->validated('budget_id'));
        $varianceData = $this->varianceService->calculateVariance($budget);

        $filename = 'budget_variance_' . now()->format('Y-m-d_H-i-s') . '_' . Str::ulid() . '.xlsx';
        $filePath = 'exports/' . $filename;

        Excel::store(new BudgetVarianceExport($varianceData), $filePath, 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($filePath),
            'filename' => $filename,
        ]);
    }
}
