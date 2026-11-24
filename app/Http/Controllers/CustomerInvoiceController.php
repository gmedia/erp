<?php

namespace App\Http\Controllers;

use App\Actions\AccountingPosting\PostCustomerInvoiceJournalAction;
use App\Actions\AccountingPosting\ReverseDocumentJournalAction;
use App\Actions\CustomerInvoices\ExportCustomerInvoicesAction;
use App\Actions\CustomerInvoices\IndexCustomerInvoicesAction;
use App\Actions\CustomerInvoices\SyncCustomerInvoiceItemsAction;
use App\DTOs\CustomerInvoices\UpdateCustomerInvoiceData;
use App\Http\Controllers\Concerns\CancelsPostedDocuments;
use App\Http\Controllers\Concerns\DeletesOnlyDraftDocuments;
use App\Http\Controllers\Concerns\LoadsResourceRelations;
use App\Http\Controllers\Concerns\StoresItemsInTransaction;
use App\Http\Requests\CustomerInvoices\ExportCustomerInvoiceRequest;
use App\Http\Requests\CustomerInvoices\IndexCustomerInvoiceRequest;
use App\Http\Requests\CustomerInvoices\StoreCustomerInvoiceRequest;
use App\Http\Requests\CustomerInvoices\UpdateCustomerInvoiceRequest;
use App\Http\Resources\CustomerInvoices\CustomerInvoiceCollection;
use App\Http\Resources\CustomerInvoices\CustomerInvoiceResource;
use App\Models\CustomerInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CustomerInvoiceController extends Controller
{
    use CancelsPostedDocuments;
    use DeletesOnlyDraftDocuments;
    use LoadsResourceRelations;
    use StoresItemsInTransaction;

    public function index(IndexCustomerInvoiceRequest $request, IndexCustomerInvoicesAction $action): JsonResponse
    {
        $customerInvoices = $action->execute($request);

        return (new CustomerInvoiceCollection($customerInvoices))->response();
    }

    public function store(
        StoreCustomerInvoiceRequest $request,
        SyncCustomerInvoiceItemsAction $syncItems,
        PostCustomerInvoiceJournalAction $postJournal
    ): JsonResponse {
        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);

        $validated['created_by'] = Auth::id();

        $isSentOnCreate = ($validated['status'] ?? null) === 'sent';

        if ($isSentOnCreate) {
            $validated['sent_by'] = Auth::id();
            $validated['sent_at'] = now()->toIso8601String();
        }

        $customerInvoice = $this->storeWithSyncedItems(
            attributes: $validated,
            items: $items,
            creator: static fn (array $attributes): CustomerInvoice => CustomerInvoice::create($attributes),
            assignDocumentNumber: function (CustomerInvoice $customerInvoice): void {
                $this->assignSequentialDocumentNumber($customerInvoice, 'invoice_number', 'INV');
            },
            syncItems: function (CustomerInvoice $customerInvoice, array $items) use ($syncItems): void {
                $syncItems->execute($customerInvoice, $items);
            },
            afterCommit: $isSentOnCreate
                ? function (CustomerInvoice $customerInvoice) use ($postJournal): void {
                    $postJournal->execute($customerInvoice->refresh());
                }
            : null,
        );

        return (new CustomerInvoiceResource($this->loadResourceRelations($customerInvoice)))
            ->response()
            ->setStatusCode(201);
    }

    public function show(CustomerInvoice $customerInvoice): JsonResponse
    {
        return (new CustomerInvoiceResource($this->loadResourceRelations($customerInvoice)))->response();
    }

    public function update(
        UpdateCustomerInvoiceRequest $request,
        CustomerInvoice $customerInvoice,
        SyncCustomerInvoiceItemsAction $syncItems,
        PostCustomerInvoiceJournalAction $postJournal,
        ReverseDocumentJournalAction $reverseJournal,
    ): JsonResponse {
        $validated = $request->validated();
        $items = $validated['items'] ?? null;
        unset($validated['items']);

        $isPostedCancel = in_array($validated['status'] ?? null, ['cancelled', 'void'], true);

        if ($isPostedCancel && $customerInvoice->journal_entry_id !== null) {
            $this->cancelPostedDocument(
                document: $customerInvoice,
                attributes: $validated,
                blockers: [
                    [
                        static fn (CustomerInvoice $invoice): bool => $invoice->receiptAllocations()
                            ->whereHas('receipt', fn ($q) => $q->whereIn('status', ['confirmed', 'reconciled']))
                            ->exists(),
                        __('Customer invoice still has active receipt allocations. Remove them before cancelling.'),
                    ],
                    [
                        static fn (CustomerInvoice $invoice): bool => $invoice->creditNotes()
                            ->where('status', 'applied')
                            ->exists(),
                        __('Customer invoice still has applied credit notes. Void them before cancelling.'),
                    ],
                ],
                reference: $customerInvoice->invoice_number,
                label: 'Invoice',
                reverseJournal: $reverseJournal,
            );

            return (new CustomerInvoiceResource($this->loadResourceRelations($customerInvoice)))->response();
        }

        $isNewlySent = ($validated['status'] ?? null) === 'sent' && $customerInvoice->sent_at === null;

        if ($isNewlySent) {
            $validated['sent_by'] = Auth::id();
            $validated['sent_at'] = now()->toIso8601String();
        }

        $this->updateWithSyncedItems(
            model: $customerInvoice,
            attributes: $validated,
            items: $items,
            payloadResolver: static fn (array $attributes): array => UpdateCustomerInvoiceData::fromArray(
                $attributes
            )->toArray(),
            syncItems: function (CustomerInvoice $customerInvoice, array $items) use ($syncItems): void {
                $syncItems->execute($customerInvoice, $items);
            },
            afterCommit: $isNewlySent
                ? function (CustomerInvoice $customerInvoice) use ($postJournal): void {
                    $postJournal->execute($customerInvoice->refresh());
                }
            : null,
        );

        return (new CustomerInvoiceResource($this->loadResourceRelations($customerInvoice)))->response();
    }

    public function destroy(CustomerInvoice $customerInvoice): JsonResponse
    {
        return $this->destroyDraftDocument($customerInvoice);
    }

    public function export(ExportCustomerInvoiceRequest $request, ExportCustomerInvoicesAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    protected function resourceRelations(): array
    {
        return [
            'customer', 'branch', 'fiscalYear', 'creator', 'sender',
            'items.product', 'items.account', 'items.unit',
        ];
    }
}
