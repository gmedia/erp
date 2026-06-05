<?php

namespace App\Http\Controllers;

use App\Actions\Budgets\StoreBudgetAction;
use App\Actions\Budgets\UpdateBudgetAction;
use App\Domain\Budgets\BudgetFilterService;
use App\Exports\BudgetExport;
use App\Http\Controllers\Concerns\LoadsResourceRelations;
use App\Http\Controllers\Concerns\StoresItemsInTransaction;
use App\Http\Requests\Budgets\IndexBudgetRequest;
use App\Http\Requests\Budgets\StoreBudgetRequest;
use App\Http\Requests\Budgets\UpdateBudgetRequest;
use App\Http\Resources\Budgets\BudgetCollection;
use App\Http\Resources\Budgets\BudgetResource;
use App\Models\Budget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class BudgetController extends Controller
{
    use LoadsResourceRelations;
    use StoresItemsInTransaction;

    public function __construct(
        private BudgetFilterService $filterService,
    ) {}

    public function index(IndexBudgetRequest $request): JsonResponse
    {
        $query = Budget::query()->with(['fiscalYear', 'creator']);

        if ($request->filled('search')) {
            $this->filterService->applySearch(
                $query,
                $request->string('search')->toString(),
                ['name', 'description'],
            );
        }

        $this->filterService->applyAdvancedFilters($query, [
            'fiscal_year_id' => $request->get('fiscal_year_id'),
            'budget_type' => $request->get('budget_type'),
            'status' => $request->get('status'),
        ]);

        $this->filterService->applySorting(
            $query,
            $request->get('sort_by', 'created_at'),
            $this->filterService->normalizeSortDirection($request->get('sort_direction', 'desc')),
            ['name', 'budget_type', 'status', 'total_amount', 'created_at'],
        );

        $paginated = $query->paginate(
            $request->integer('per_page', 15),
            ['*'],
            'page',
            $request->integer('page', 1),
        );

        return (new BudgetCollection($paginated))->response();
    }

    public function store(StoreBudgetRequest $request, StoreBudgetAction $action): JsonResponse
    {
        $budget = $action->execute($request->validated());

        return (new BudgetResource($budget->load(['fiscalYear', 'creator', 'approver'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Budget $budget): JsonResponse
    {
        return (new BudgetResource($this->loadResourceRelations($budget)))->response();
    }

    public function update(UpdateBudgetRequest $request, Budget $budget, UpdateBudgetAction $action): JsonResponse
    {
        $budget = $action->execute($budget, $request->validated());

        return (new BudgetResource($budget->load(['fiscalYear', 'creator', 'approver'])))->response();
    }

    public function destroy(Budget $budget): JsonResponse
    {
        if ($budget->status !== 'draft') {
            return response()->json([
                'message' => 'Only draft budgets can be deleted.',
            ], 422);
        }

        return $this->destroyModel($budget);
    }

    public function export(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'fiscal_year_id', 'budget_type', 'status']);
        $filename = 'budgets_export_' . now()->format('Y-m-d_H-i-s') . '_' . Str::ulid() . '.xlsx';
        $filePath = 'exports/' . $filename;

        Excel::store(new BudgetExport($filters), $filePath, 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($filePath),
            'filename' => $filename,
        ]);
    }

    public function approve(Budget $budget): JsonResponse
    {
        if ($budget->status !== 'draft') {
            return response()->json([
                'message' => 'Only draft budgets can be approved.',
            ], 422);
        }

        $budget->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return (new BudgetResource($budget->load(['fiscalYear', 'creator', 'approver'])))->response();
    }

    public function lock(Budget $budget): JsonResponse
    {
        if ($budget->status !== 'approved') {
            return response()->json([
                'message' => 'Only approved budgets can be locked.',
            ], 422);
        }

        $budget->update([
            'status' => 'locked',
        ]);

        return (new BudgetResource($budget->load(['fiscalYear', 'creator', 'approver'])))->response();
    }

    protected function resourceRelations(): array
    {
        return ['lines.account', 'fiscalYear', 'creator', 'approver'];
    }
}
