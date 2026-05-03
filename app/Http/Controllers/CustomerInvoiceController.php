<?php

namespace App\Http\Controllers;

use App\Actions\CustomerInvoices\ExportCustomerInvoicesAction;
use App\Actions\CustomerInvoices\IndexCustomerInvoicesAction;
use App\Actions\CustomerInvoices\SyncCustomerInvoiceItemsAction;
use App\DTOs\CustomerInvoices\UpdateCustomerInvoiceData;
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
    use LoadsResourceRelations;
    use StoresItemsInTransaction;

    public function index(IndexCustomerInvoiceRequest $request, IndexCustomerInvoicesAction $action): JsonResponse
    {
        $customerInvoices = $action->execute($request);

        return (new CustomerInvoiceCollection($customerInvoices))->response();
    }

    public function store(StoreCustomerInvoiceRequest $request, SyncCustomerInvoiceItemsAction $syncItems): JsonResponse
    {
        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);

        $validated['created_by'] = Auth::id();

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
        );

        return (new CustomerInvoiceResource($this->loadResourceRelations($customerInvoice)))->response()->setStatusCode(201);
    }

    public function show(CustomerInvoice $customerInvoice): JsonResponse
    {
        return (new CustomerInvoiceResource($this->loadResourceRelations($customerInvoice)))->response();
    }

    public function update(
        UpdateCustomerInvoiceRequest $request,
        CustomerInvoice $customerInvoice,
        SyncCustomerInvoiceItemsAction $syncItems
    ): JsonResponse {
        $validated = $request->validated();
        $items = $validated['items'] ?? null;
        unset($validated['items']);

        if (($validated['status'] ?? null) === 'sent' && $customerInvoice->sent_at === null) {
            $validated['sent_by'] = Auth::id();
            $validated['sent_at'] = now()->toIso8601String();
        }

        $this->updateWithSyncedItems(
            model: $customerInvoice,
            attributes: $validated,
            items: $items,
            payloadResolver: static fn (array $attributes): array => UpdateCustomerInvoiceData::fromArray($attributes)->toArray(),
            syncItems: function (CustomerInvoice $customerInvoice, array $items) use ($syncItems): void {
                $syncItems->execute($customerInvoice, $items);
            },
        );

        return (new CustomerInvoiceResource($this->loadResourceRelations($customerInvoice)))->response();
    }

    public function destroy(CustomerInvoice $customerInvoice): JsonResponse
    {
        return $this->destroyModel($customerInvoice);
    }

    public function export(ExportCustomerInvoiceRequest $request, ExportCustomerInvoicesAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    protected function resourceRelations(): array
    {
        return ['customer', 'branch', 'fiscalYear', 'creator', 'sender', 'items.product', 'items.account', 'items.unit'];
    }
}
