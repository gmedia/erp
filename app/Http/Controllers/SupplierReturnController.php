<?php

namespace App\Http\Controllers;

use App\Actions\SupplierReturns\ExportSupplierReturnsAction;
use App\Actions\SupplierReturns\IndexSupplierReturnsAction;
use App\Actions\SupplierReturns\SyncSupplierReturnItemsAction;
use App\DTOs\SupplierReturns\UpdateSupplierReturnData;
use App\Http\Controllers\Concerns\StoresItemsInTransaction;
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
    use StoresItemsInTransaction;

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

        $supplierReturn = $this->storeWithSyncedItems(
            attributes: $validated,
            items: $items,
            creator: static fn (array $attributes): SupplierReturn => SupplierReturn::create($attributes),
            assignDocumentNumber: function (SupplierReturn $supplierReturn): void {
                $this->assignSequentialDocumentNumber($supplierReturn, 'return_number', 'SR');
            },
            syncItems: function (SupplierReturn $supplierReturn, array $items) use ($syncItems): void {
                $syncItems->execute($supplierReturn, $items);
            },
        );

        $supplierReturn->load([
            'purchaseOrder',
            'goodsReceipt',
            'supplier',
            'warehouse',
            'creator',
            'items.product',
            'items.unit',
        ]);

        return (new SupplierReturnResource($supplierReturn))->response()->setStatusCode(201);
    }

    public function show(SupplierReturn $supplierReturn): JsonResponse
    {
        $supplierReturn->load([
            'purchaseOrder',
            'goodsReceipt',
            'supplier',
            'warehouse',
            'creator',
            'items.product',
            'items.unit',
        ]);

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

        $supplierReturn->load([
            'purchaseOrder',
            'goodsReceipt',
            'supplier',
            'warehouse',
            'creator',
            'items.product',
            'items.unit',
        ]);

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
