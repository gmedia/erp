<?php

namespace App\Http\Controllers;

use App\Actions\AccountingPosting\ReverseDocumentJournalAction;
use App\Actions\CreditNotes\ApplyCreditNoteAction;
use App\Actions\CreditNotes\ExportCreditNotesAction;
use App\Actions\CreditNotes\IndexCreditNotesAction;
use App\Actions\CreditNotes\SyncCreditNoteItemsAction;
use App\DTOs\CreditNotes\UpdateCreditNoteData;
use App\Http\Controllers\Concerns\CancelsPostedDocuments;
use App\Http\Controllers\Concerns\DeletesOnlyDraftDocuments;
use App\Http\Controllers\Concerns\LoadsResourceRelations;
use App\Http\Controllers\Concerns\StoresItemsInTransaction;
use App\Http\Requests\CreditNotes\ExportCreditNoteRequest;
use App\Http\Requests\CreditNotes\IndexCreditNoteRequest;
use App\Http\Requests\CreditNotes\StoreCreditNoteRequest;
use App\Http\Requests\CreditNotes\UpdateCreditNoteRequest;
use App\Http\Resources\CreditNotes\CreditNoteCollection;
use App\Http\Resources\CreditNotes\CreditNoteResource;
use App\Models\CreditNote;
use App\Models\CustomerInvoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class CreditNoteController extends Controller
{
    use CancelsPostedDocuments;
    use DeletesOnlyDraftDocuments;
    use LoadsResourceRelations;
    use StoresItemsInTransaction;

    public function index(IndexCreditNoteRequest $request, IndexCreditNotesAction $action): JsonResponse
    {
        $creditNotes = $action->execute($request);

        return (new CreditNoteCollection($creditNotes))->response();
    }

    public function store(
        StoreCreditNoteRequest $request,
        SyncCreditNoteItemsAction $syncItems,
        ApplyCreditNoteAction $applyAction
    ): JsonResponse {
        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);

        $validated['created_by'] = Auth::id();

        $shouldApply = ($validated['status'] ?? null) === 'applied';
        if ($shouldApply) {
            $validated['status'] = 'confirmed';
        }

        try {
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
                afterCommit: $shouldApply
                    ? static function (CreditNote $creditNote) use ($applyAction): void {
                        $applyAction->execute($creditNote);
                    }
                : null,
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new CreditNoteResource($this->loadResourceRelations($creditNote)))
            ->response()
            ->setStatusCode(201);
    }

    public function show(CreditNote $creditNote): JsonResponse
    {
        return (new CreditNoteResource($this->loadResourceRelations($creditNote)))->response();
    }

    public function update(
        UpdateCreditNoteRequest $request,
        CreditNote $creditNote,
        SyncCreditNoteItemsAction $syncItems,
        ApplyCreditNoteAction $applyAction,
        ReverseDocumentJournalAction $reverseJournal,
    ): JsonResponse {
        $validated = $request->validated();
        $items = $validated['items'] ?? null;
        unset($validated['items']);

        // Issue #375 (review #426 butir 5): credit note terposting menurut status dibatalkan lewat jalur
        // ini walau jurnalnya kosong (data lama), supaya credit_note_amount invoice dihitung ulang.
        $targetStatus = $validated['status'] ?? null;
        $isPostedCancel = in_array($targetStatus, ['cancelled', 'void'], true)
            && $creditNote->status !== 'draft'
            && $creditNote->status !== $targetStatus;

        if ($isPostedCancel) {
            $this->cancelPostedDocument(
                document: $creditNote,
                attributes: $validated,
                blockers: [],
                reference: $creditNote->credit_note_number,
                label: 'Credit Note',
                reverseJournal: $reverseJournal,
                release: static function (Model $document): void {
                    /** @var CreditNote $document */
                    /** @var CustomerInvoice|null $invoice */
                    $invoice = $document->customerInvoice()->lockForUpdate()->first();
                    if ($invoice === null) {
                        return;
                    }

                    // Hanya credit note applied yang mengurangi invoice; yang baru confirmed tidak.
                    $creditNoteAmount = (float) $invoice->creditNotes()
                        ->where('status', 'applied')
                        ->sum('grand_total');
                    $invoice->update([
                        'credit_note_amount' => (string) $creditNoteAmount,
                        'amount_due' => (string) (
                            (float) $invoice->grand_total - (float) $invoice->amount_received - $creditNoteAmount
                        ),
                    ]);
                    $invoice->updatePaymentStatus();
                },
            );

            return (new CreditNoteResource($this->loadResourceRelations($creditNote)))->response();
        }

        $shouldApply = ($validated['status'] ?? null) === 'applied'
            && $creditNote->status !== 'applied';
        if ($shouldApply) {
            $validated['status'] = 'confirmed';
        }

        if (($validated['status'] ?? null) === 'confirmed' && $creditNote->confirmed_at === null) {
            $validated['confirmed_by'] = Auth::id();
            $validated['confirmed_at'] = now()->toIso8601String();
        }

        try {
            $this->updateWithSyncedItems(
                model: $creditNote,
                attributes: $validated,
                items: $items,
                payloadResolver: static fn (array $attributes): array => UpdateCreditNoteData::fromArray(
                    $attributes
                )->toArray(),
                syncItems: function (CreditNote $creditNote, array $items) use ($syncItems): void {
                    $syncItems->execute($creditNote, $items);
                },
                afterCommit: $shouldApply
                    ? static function (CreditNote $creditNote) use ($applyAction): void {
                        $applyAction->execute($creditNote);
                    }
                : null,
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new CreditNoteResource($this->loadResourceRelations($creditNote)))->response();
    }

    public function destroy(CreditNote $creditNote): JsonResponse
    {
        return $this->destroyDraftDocument($creditNote);
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
        return [
            'customer', 'customerInvoice', 'branch', 'fiscalYear',
            'creator', 'confirmer', 'items.product', 'items.account',
        ];
    }
}
