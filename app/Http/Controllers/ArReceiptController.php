<?php

namespace App\Http\Controllers;

use App\Actions\ArReceipts\ExportArReceiptsAction;
use App\Actions\ArReceipts\IndexArReceiptsAction;
use App\Actions\ArReceipts\SyncArReceiptAllocationsAction;
use App\DTOs\ArReceipts\UpdateArReceiptData;
use App\Http\Controllers\Concerns\LoadsResourceRelations;
use App\Http\Controllers\Concerns\StoresItemsInTransaction;
use App\Http\Requests\ArReceipts\ExportArReceiptRequest;
use App\Http\Requests\ArReceipts\IndexArReceiptRequest;
use App\Http\Requests\ArReceipts\StoreArReceiptRequest;
use App\Http\Requests\ArReceipts\UpdateArReceiptRequest;
use App\Http\Resources\ArReceipts\ArReceiptCollection;
use App\Http\Resources\ArReceipts\ArReceiptResource;
use App\Models\ArReceipt;
use App\Models\ArReceiptAllocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ArReceiptController extends Controller
{
    use LoadsResourceRelations;
    use StoresItemsInTransaction;

    public function index(IndexArReceiptRequest $request, IndexArReceiptsAction $action): JsonResponse
    {
        $arReceipts = $action->execute($request);

        return (new ArReceiptCollection($arReceipts))->response();
    }

    public function store(StoreArReceiptRequest $request, SyncArReceiptAllocationsAction $syncAllocations): JsonResponse
    {
        $validated = $request->validated();
        $allocations = $validated['allocations'];
        unset($validated['allocations']);

        $validated['created_by'] = Auth::id();

        $arReceipt = $this->storeWithSyncedItems(
            attributes: $validated,
            items: $allocations,
            creator: static fn (array $attributes): ArReceipt => ArReceipt::create($attributes),
            assignDocumentNumber: function (ArReceipt $arReceipt): void {
                $this->assignSequentialDocumentNumber($arReceipt, 'receipt_number', 'RCV');
            },
            syncItems: function (ArReceipt $arReceipt, array $allocations) use ($syncAllocations): void {
                $syncAllocations->execute($arReceipt, $allocations);
            },
        );

        return (new ArReceiptResource($this->loadResourceRelations($arReceipt)))->response()->setStatusCode(201);
    }

    public function show(ArReceipt $arReceipt): JsonResponse
    {
        return (new ArReceiptResource($this->loadResourceRelations($arReceipt)))->response();
    }

    public function update(
        UpdateArReceiptRequest $request,
        ArReceipt $arReceipt,
        SyncArReceiptAllocationsAction $syncAllocations
    ): JsonResponse {
        $validated = $request->validated();
        $allocations = $validated['allocations'] ?? null;
        unset($validated['allocations']);

        if (($validated['status'] ?? null) === 'confirmed' && $arReceipt->confirmed_at === null) {
            $validated['confirmed_by'] = Auth::id();
            $validated['confirmed_at'] = now()->toIso8601String();
        }

        $this->updateWithSyncedItems(
            model: $arReceipt,
            attributes: $validated,
            items: $allocations,
            payloadResolver: static fn (array $attributes): array => UpdateArReceiptData::fromArray($attributes)->toArray(),
            syncItems: function (ArReceipt $arReceipt, array $allocations) use ($syncAllocations): void {
                $syncAllocations->execute($arReceipt, $allocations);
            },
        );

        return (new ArReceiptResource($this->loadResourceRelations($arReceipt)))->response();
    }

    public function destroy(ArReceipt $arReceipt): JsonResponse
    {
        DB::transaction(function () use ($arReceipt) {
            /** @var ArReceiptAllocation $allocation */
            foreach ($arReceipt->allocations()->with('customerInvoice')->get() as $allocation) {
                $invoice = $allocation->customerInvoice;

                $newAmountReceived = (float) $invoice->amount_received - (float) $allocation->allocated_amount;
                $invoice->update([
                    'amount_received' => (string) max(0, $newAmountReceived),
                    'amount_due' => (string) ((float) $invoice->grand_total - max(0, $newAmountReceived) - (float) $invoice->credit_note_amount),
                ]);
                $invoice->updatePaymentStatus();
            }
            $arReceipt->delete();
        });

        return response()->json(null, 204);
    }

    public function export(ExportArReceiptRequest $request, ExportArReceiptsAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    protected function resourceRelations(): array
    {
        return ['customer', 'branch', 'fiscalYear', 'bankAccount', 'creator', 'confirmer', 'allocations.customerInvoice'];
    }
}
