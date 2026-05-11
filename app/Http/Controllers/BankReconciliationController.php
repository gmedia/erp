<?php

namespace App\Http\Controllers;

use App\Actions\BankReconciliations\CompleteBankReconciliationAction;
use App\Actions\BankReconciliations\ExportBankReconciliationsAction;
use App\Actions\BankReconciliations\IndexBankReconciliationsAction;
use App\Http\Requests\BankReconciliations\ExportBankReconciliationRequest;
use App\Http\Requests\BankReconciliations\IndexBankReconciliationRequest;
use App\Http\Requests\BankReconciliations\StoreBankReconciliationRequest;
use App\Http\Requests\BankReconciliations\UpdateBankReconciliationRequest;
use App\Http\Resources\BankReconciliations\BankReconciliationCollection;
use App\Http\Resources\BankReconciliations\BankReconciliationResource;
use App\Models\BankReconciliation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankReconciliationController extends Controller
{
    public function index(IndexBankReconciliationRequest $request, IndexBankReconciliationsAction $action): JsonResponse
    {
        return (new BankReconciliationCollection($action->execute($request)))->response();
    }

    public function store(StoreBankReconciliationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $items = $data['items'] ?? [];
        unset($data['items']);
        $data['status'] ??= 'draft';
        $data['reconciled_balance'] ??= 0;
        $data['difference'] ??= (float) $data['statement_balance'] - (float) $data['book_balance'];
        $data['created_by'] = auth()->id();

        $bankReconciliation = BankReconciliation::create($data);
        foreach ($items as $item) {
            $bankReconciliation->items()->create($item);
        }

        return (new BankReconciliationResource($bankReconciliation->load(['account', 'fiscalYear', 'items', 'completedBy', 'creator'])))->response()->setStatusCode(201);
    }

    public function show(BankReconciliation $bankReconciliation): JsonResponse
    {
        return (new BankReconciliationResource($bankReconciliation->load(['account', 'fiscalYear', 'items', 'completedBy', 'creator'])))->response();
    }

    public function update(UpdateBankReconciliationRequest $request, BankReconciliation $bankReconciliation): JsonResponse
    {
        $data = $request->validated();
        $items = $data['items'] ?? null;
        unset($data['items']);
        $bankReconciliation->update($data);

        if (is_array($items)) {
            $bankReconciliation->items()->delete();
            foreach ($items as $item) {
                $bankReconciliation->items()->create($item);
            }
        }

        return (new BankReconciliationResource($bankReconciliation->refresh()->load(['account', 'fiscalYear', 'items', 'completedBy', 'creator'])))->response();
    }

    public function destroy(BankReconciliation $bankReconciliation): JsonResponse
    {
        $bankReconciliation->items()->delete();
        $bankReconciliation->delete();

        return response()->json(null, 204);
    }

    public function export(ExportBankReconciliationRequest $request, ExportBankReconciliationsAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    public function complete(BankReconciliation $bankReconciliation, CompleteBankReconciliationAction $action): JsonResponse
    {
        return (new BankReconciliationResource($action->execute($bankReconciliation)))->response();
    }

    public function addItem(Request $request, BankReconciliation $bankReconciliation): JsonResponse
    {
        $item = $bankReconciliation->items()->create($request->validate([
            'journal_entry_line_id' => ['nullable', 'integer', 'exists:journal_entry_lines,id'],
            'transaction_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'debit' => ['nullable', 'numeric', 'min:0'],
            'credit' => ['nullable', 'numeric', 'min:0'],
            'type' => ['nullable', 'string', 'max:30'],
            'is_reconciled' => ['boolean'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]));

        return response()->json(['data' => $item], 201);
    }

    public function removeItem(BankReconciliation $bankReconciliation, int $item): JsonResponse
    {
        $bankReconciliation->items()->whereKey($item)->delete();

        return response()->json(null, 204);
    }
}
