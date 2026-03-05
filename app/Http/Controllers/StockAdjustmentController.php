<?php

namespace App\Http\Controllers;

use App\Actions\StockAdjustments\ExportStockAdjustmentsAction;
use App\Actions\StockAdjustments\IndexStockAdjustmentsAction;
use App\Actions\StockAdjustments\SyncStockAdjustmentItemsAction;
use App\DTOs\StockAdjustments\UpdateStockAdjustmentData;
use App\Http\Requests\StockAdjustments\ExportStockAdjustmentRequest;
use App\Http\Requests\StockAdjustments\IndexStockAdjustmentRequest;
use App\Http\Requests\StockAdjustments\StoreStockAdjustmentRequest;
use App\Http\Requests\StockAdjustments\UpdateStockAdjustmentRequest;
use App\Http\Resources\StockAdjustments\StockAdjustmentCollection;
use App\Http\Resources\StockAdjustments\StockAdjustmentResource;
use App\Models\StockAdjustment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    public function index(IndexStockAdjustmentRequest $request, IndexStockAdjustmentsAction $action): JsonResponse
    {
        $adjustments = $action->execute($request);

        return (new StockAdjustmentCollection($adjustments))->response();
    }

    public function store(StoreStockAdjustmentRequest $request, SyncStockAdjustmentItemsAction $syncItems): JsonResponse
    {
        $data = $request->validated();
        $items = $data['items'];
        unset($data['items']);

        $data['created_by'] = Auth::id();

        $adjustment = DB::transaction(function () use ($data, $items, $syncItems) {
            $adjustment = StockAdjustment::create($data);

            if (empty($adjustment->adjustment_number)) {
                $adjustment->update([
                    'adjustment_number' => 'SA-' . now()->format('Y') . '-' . str_pad((string) $adjustment->id, 6, '0', STR_PAD_LEFT),
                ]);
            }

            $syncItems->execute($adjustment, $items);

            return $adjustment;
        });

        $adjustment->load(['warehouse', 'inventoryStocktake', 'items.product', 'items.unit']);

        return (new StockAdjustmentResource($adjustment))
            ->response()
            ->setStatusCode(201);
    }

    public function show(StockAdjustment $stockAdjustment): JsonResponse
    {
        $stockAdjustment->load([
            'warehouse',
            'inventoryStocktake',
            'journalEntry',
            'approvedBy',
            'createdBy',
            'items.product',
            'items.unit',
        ]);

        return (new StockAdjustmentResource($stockAdjustment))->response();
    }

    public function update(UpdateStockAdjustmentRequest $request, StockAdjustment $stockAdjustment, SyncStockAdjustmentItemsAction $syncItems): JsonResponse
    {
        $validated = $request->validated();
        $items = $validated['items'] ?? null;
        unset($validated['items']);

        if (($validated['status'] ?? null) === 'approved' && $stockAdjustment->approved_at === null) {
            $validated['approved_by'] = Auth::id();
            $validated['approved_at'] = now()->toIso8601String();
        }

        $dto = UpdateStockAdjustmentData::fromArray($validated);

        DB::transaction(function () use ($stockAdjustment, $dto, $items, $syncItems) {
            $stockAdjustment->update($dto->toArray());

            if (is_array($items)) {
                $syncItems->execute($stockAdjustment, $items);
            }
        });

        $stockAdjustment->load(['warehouse', 'inventoryStocktake', 'items.product', 'items.unit']);

        return (new StockAdjustmentResource($stockAdjustment))->response();
    }

    public function destroy(StockAdjustment $stockAdjustment): JsonResponse
    {
        $stockAdjustment->update(['status' => 'cancelled']);

        return response()->json(null, 204);
    }

    public function export(ExportStockAdjustmentRequest $request, ExportStockAdjustmentsAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
