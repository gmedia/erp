<?php

namespace App\Http\Controllers;

use App\Actions\CreditNotes\ApplyCreditNoteAction;
use App\Actions\CreditNotes\ExportCreditNotesAction;
use App\Actions\CreditNotes\IndexCreditNotesAction;
use App\Actions\CreditNotes\SyncCreditNoteItemsAction;
use App\DTOs\CreditNotes\UpdateCreditNoteData;
use App\Http\Controllers\Concerns\LoadsResourceRelations;
use App\Http\Controllers\Concerns\StoresItemsInTransaction;
use App\Http\Requests\CreditNotes\ExportCreditNoteRequest;
use App\Http\Requests\CreditNotes\IndexCreditNoteRequest;
use App\Http\Requests\CreditNotes\StoreCreditNoteRequest;
use App\Http\Requests\CreditNotes\UpdateCreditNoteRequest;
use App\Http\Resources\CreditNotes\CreditNoteCollection;
use App\Http\Resources\CreditNotes\CreditNoteResource;
use App\Models\CreditNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class CreditNoteController extends Controller
{
    use LoadsResourceRelations;
    use StoresItemsInTransaction;

    public function index(IndexCreditNoteRequest $request, IndexCreditNotesAction $action): JsonResponse
    {
        $creditNotes = $action->execute($request);

        return (new CreditNoteCollection($creditNotes))->response();
    }

    public function store(StoreCreditNoteRequest $request, SyncCreditNoteItemsAction $syncItems): JsonResponse
    {
        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);

        $validated['created_by'] = Auth::id();

        $creditNote = $this->storeWithSyncedItems(
            attributes: $validated,
            items: $items,
            creator: static fn (array $attributes): CreditNote => CreditNote::create($attributes),
            assignDocumentNumber: function (CreditNote $creditNote): void {
                $this->assignSequentialDocumentNumber($creditNote, 'credit_note_number', 'CN');
            },
            syncItems: function (CreditNote $creditNote, array $items) use ($syncItems): void {
                $syncItems->execute($creditNote, $items);
            },
        );

        return (new CreditNoteResource($this->loadResourceRelations($creditNote)))->response()->setStatusCode(201);
    }

    public function show(CreditNote $creditNote): JsonResponse
    {
        return (new CreditNoteResource($this->loadResourceRelations($creditNote)))->response();
    }

    public function update(
        UpdateCreditNoteRequest $request,
        CreditNote $creditNote,
        SyncCreditNoteItemsAction $syncItems
    ): JsonResponse {
        $validated = $request->validated();
        $items = $validated['items'] ?? null;
        unset($validated['items']);

        if (($validated['status'] ?? null) === 'confirmed' && $creditNote->confirmed_at === null) {
            $validated['confirmed_by'] = Auth::id();
            $validated['confirmed_at'] = now()->toIso8601String();
        }

        $this->updateWithSyncedItems(
            model: $creditNote,
            attributes: $validated,
            items: $items,
            payloadResolver: static fn (array $attributes): array => UpdateCreditNoteData::fromArray($attributes)->toArray(),
            syncItems: function (CreditNote $creditNote, array $items) use ($syncItems): void {
                $syncItems->execute($creditNote, $items);
            },
        );

        return (new CreditNoteResource($this->loadResourceRelations($creditNote)))->response();
    }

    public function destroy(CreditNote $creditNote): JsonResponse
    {
        return $this->destroyModel($creditNote);
    }

    public function apply(CreditNote $creditNote, ApplyCreditNoteAction $action): JsonResponse
    {
        try {
            $creditNote = $action->execute($creditNote);

            return (new CreditNoteResource($this->loadResourceRelations($creditNote)))->response();
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function export(ExportCreditNoteRequest $request, ExportCreditNotesAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    protected function resourceRelations(): array
    {
        return ['customer', 'customerInvoice', 'branch', 'fiscalYear', 'creator', 'confirmer', 'items.product', 'items.account'];
    }
}
