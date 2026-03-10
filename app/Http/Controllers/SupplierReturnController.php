<?php

namespace App\Http\Controllers;

use App\Actions\SupplierReturns\ExportSupplierReturnsAction;
use App\Actions\SupplierReturns\IndexSupplierReturnsAction;
use App\Actions\SupplierReturns\SyncSupplierReturnItemsAction;
use App\DTOs\SupplierReturns\UpdateSupplierReturnData;
use App\Http\Requests\SupplierReturns\ExportSupplierReturnRequest;
use App\Http\Requests\SupplierReturns\IndexSupplierReturnRequest;
use App\Http\Requests\SupplierReturns\StoreSupplierReturnRequest;
use App\Http\Requests\SupplierReturns\UpdateSupplierReturnRequest;
use App\Http\Resources\SupplierReturns\SupplierReturnCollection;
use App\Http\Resources\SupplierReturns\SupplierReturnResource;
use App\Models\SupplierReturn;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupplierReturnController extends Controller
{
    public function index(IndexSupplierReturnRequest $request, IndexSupplierReturnsAction $action): JsonResponse
    {
        $supplierReturns = $action->execute($request);

        return (new SupplierReturnCollection($supplierReturns))->response();
    }

    public function store(StoreSupplierReturnRequest $request, SyncSupplierReturnItemsAction $syncItems): JsonResponse
    {
        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);

        $validated['created_by'] = Auth::id();

        $supplierReturn = DB::transaction(function () use ($validated, $items, $syncItems) {
            $supplierReturn = SupplierReturn::create($validated);

            if (empty($supplierReturn->return_number)) {
                $supplierReturn->update([
                    'return_number' => 'SR-' . now()->format('Y') . '-' . str_pad((string) $supplierReturn->id, 6, '0', STR_PAD_LEFT),
                ]);
            }

            $syncItems->execute($supplierReturn, $items);

            return $supplierReturn;
        });

        $supplierReturn->load(['purchaseOrder', 'goodsReceipt', 'supplier', 'warehouse', 'creator', 'items.product', 'items.unit']);

        return (new SupplierReturnResource($supplierReturn))->response()->setStatusCode(201);
    }

    public function show(SupplierReturn $supplierReturn): JsonResponse
    {
        $supplierReturn->load(['purchaseOrder', 'goodsReceipt', 'supplier', 'warehouse', 'creator', 'items.product', 'items.unit']);

        return (new SupplierReturnResource($supplierReturn))->response();
    }

    public function update(
        UpdateSupplierReturnRequest $request,
        SupplierReturn $supplierReturn,
        SyncSupplierReturnItemsAction $syncItems
    ): JsonResponse {
        $validated = $request->validated();
        $items = $validated['items'] ?? null;
        unset($validated['items']);

        $dto = UpdateSupplierReturnData::fromArray($validated);

        DB::transaction(function () use ($supplierReturn, $dto, $items, $syncItems) {
            $supplierReturn->update($dto->toArray());

            if (is_array($items)) {
                $syncItems->execute($supplierReturn, $items);
            }
        });

        $supplierReturn->load(['purchaseOrder', 'goodsReceipt', 'supplier', 'warehouse', 'creator', 'items.product', 'items.unit']);

        return (new SupplierReturnResource($supplierReturn))->response();
    }

    public function destroy(SupplierReturn $supplierReturn): JsonResponse
    {
        $supplierReturn->delete();

        return response()->json(null, 204);
    }

    public function export(ExportSupplierReturnRequest $request, ExportSupplierReturnsAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
