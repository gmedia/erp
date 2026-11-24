<?php

namespace App\Http\Controllers;

use App\Actions\AccountingPosting\PostSupplierBillJournalAction;
use App\Actions\AccountingPosting\ReverseDocumentJournalAction;
use App\Actions\SupplierBills\ExportSupplierBillsAction;
use App\Actions\SupplierBills\IndexSupplierBillsAction;
use App\Actions\SupplierBills\SyncSupplierBillItemsAction;
use App\DTOs\SupplierBills\UpdateSupplierBillData;
use App\Http\Controllers\Concerns\CancelsPostedDocuments;
use App\Http\Controllers\Concerns\DeletesOnlyDraftDocuments;
use App\Http\Controllers\Concerns\LoadsResourceRelations;
use App\Http\Controllers\Concerns\StoresItemsInTransaction;
use App\Http\Requests\SupplierBills\ExportSupplierBillRequest;
use App\Http\Requests\SupplierBills\IndexSupplierBillRequest;
use App\Http\Requests\SupplierBills\StoreSupplierBillRequest;
use App\Http\Requests\SupplierBills\UpdateSupplierBillRequest;
use App\Http\Resources\SupplierBills\SupplierBillCollection;
use App\Http\Resources\SupplierBills\SupplierBillResource;
use App\Models\SupplierBill;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SupplierBillController extends Controller
{
    use CancelsPostedDocuments;
    use DeletesOnlyDraftDocuments;
    use LoadsResourceRelations;
    use StoresItemsInTransaction;

    public function index(IndexSupplierBillRequest $request, IndexSupplierBillsAction $action): JsonResponse
    {
        $supplierBills = $action->execute($request);

        return (new SupplierBillCollection($supplierBills))->response();
    }

    public function store(
        StoreSupplierBillRequest $request,
        SyncSupplierBillItemsAction $syncItems,
        PostSupplierBillJournalAction $postJournal
    ): JsonResponse {
        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);

        $validated['created_by'] = Auth::id();

        $isConfirmedOnCreate = ($validated['status'] ?? null) === 'confirmed';

        if ($isConfirmedOnCreate) {
            $validated['confirmed_by'] = Auth::id();
            $validated['confirmed_at'] = now()->toIso8601String();
        }

        $supplierBill = $this->storeWithSyncedItems(
            attributes: $validated,
            items: $items,
            creator: static fn (array $attributes): SupplierBill => SupplierBill::create($attributes),
            assignDocumentNumber: function (SupplierBill $supplierBill): void {
                $this->assignSequentialDocumentNumber($supplierBill, 'bill_number', 'BILL');
            },
            syncItems: function (SupplierBill $supplierBill, array $items) use ($syncItems): void {
                $syncItems->execute($supplierBill, $items);
            },
            afterCommit: $isConfirmedOnCreate
                ? function (SupplierBill $supplierBill) use ($postJournal): void {
                    $postJournal->execute($supplierBill->refresh());
                }
            : null,
        );

        return (new SupplierBillResource($this->loadResourceRelations($supplierBill)))->response()->setStatusCode(201);
    }

    public function show(SupplierBill $supplierBill): JsonResponse
    {
        return (new SupplierBillResource($this->loadResourceRelations($supplierBill)))->response();
    }

    public function update(
        UpdateSupplierBillRequest $request,
        SupplierBill $supplierBill,
        SyncSupplierBillItemsAction $syncItems,
        PostSupplierBillJournalAction $postJournal,
        ReverseDocumentJournalAction $reverseJournal,
    ): JsonResponse {
        $validated = $request->validated();
        $items = $validated['items'] ?? null;
        unset($validated['items']);

        if (($validated['status'] ?? null) === 'cancelled' && $supplierBill->journal_entry_id !== null) {
            $this->cancelPostedDocument(
                document: $supplierBill,
                attributes: array_merge($validated, ['status' => 'cancelled']),
                blockers: [
                    [
                        static fn (SupplierBill $bill): bool => $bill->paymentAllocations()
                            ->whereHas('payment', fn ($q) => $q->whereIn('status', ['confirmed', 'reconciled']))
                            ->exists(),
                        __('Supplier bill still has active payment allocations. Remove them before cancelling.'),
                    ],
                ],
                reference: $supplierBill->bill_number,
                label: 'Bill',
                reverseJournal: $reverseJournal,
            );

            return (new SupplierBillResource($this->loadResourceRelations($supplierBill)))->response();
        }

        $isNewlyConfirmed = ($validated['status'] ?? null) === 'confirmed' && $supplierBill->confirmed_at === null;

        if ($isNewlyConfirmed) {
            $validated['confirmed_by'] = Auth::id();
            $validated['confirmed_at'] = now()->toIso8601String();
        }

        $this->updateWithSyncedItems(
            model: $supplierBill,
            attributes: $validated,
            items: $items,
            payloadResolver: static function (array $attributes): array {
                return UpdateSupplierBillData::fromArray($attributes)->toArray();
            },
            syncItems: function (SupplierBill $supplierBill, array $items) use ($syncItems): void {
                $syncItems->execute($supplierBill, $items);
            },
            afterCommit: $isNewlyConfirmed
                ? function (SupplierBill $supplierBill) use ($postJournal): void {
                    $postJournal->execute($supplierBill->refresh());
                }
            : null,
        );

        return (new SupplierBillResource($this->loadResourceRelations($supplierBill)))->response();
    }

    public function destroy(SupplierBill $supplierBill): JsonResponse
    {
        return $this->destroyDraftDocument($supplierBill);
    }

    public function export(ExportSupplierBillRequest $request, ExportSupplierBillsAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    protected function resourceRelations(): array
    {
        return [
            'supplier',
            'branch',
            'fiscalYear',
            'purchaseOrder',
            'goodsReceipt',
            'creator',
            'confirmer',
            'items.product',
            'items.account',
        ];
    }
}
