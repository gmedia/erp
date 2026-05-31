<?php

namespace App\Http\Controllers;

use App\Actions\BankReconciliations\AutoMatchBankReconciliationAction;
use App\Actions\BankReconciliations\CompleteBankReconciliationAction;
use App\Actions\BankReconciliations\ExportBankReconciliationsAction;
use App\Actions\BankReconciliations\GetUnmatchedJournalLinesAction;
use App\Actions\BankReconciliations\ImportBankStatementAction;
use App\Actions\BankReconciliations\IndexBankReconciliationsAction;
use App\Actions\BankReconciliations\MatchBankReconciliationItemAction;
use App\Actions\BankReconciliations\PreviewBankStatementAction;
use App\Actions\BankReconciliations\UnmatchBankReconciliationItemAction;
use App\Http\Requests\BankReconciliations\ExportBankReconciliationRequest;
use App\Http\Requests\BankReconciliations\ImportBankStatementRequest;
use App\Http\Requests\BankReconciliations\IndexBankReconciliationRequest;
use App\Http\Requests\BankReconciliations\StoreBankReconciliationRequest;
use App\Http\Requests\BankReconciliations\UpdateBankReconciliationRequest;
use App\Http\Resources\BankReconciliations\BankReconciliationCollection;
use App\Http\Resources\BankReconciliations\BankReconciliationResource;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
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

        return (new BankReconciliationResource($bankReconciliation->load([
            'account',
            'fiscalYear',
            'items.account',
            'items.journalEntryLine.journalEntry',
            'completedBy',
            'creator',
        ])))->response()->setStatusCode(201);
    }

    public function show(BankReconciliation $bankReconciliation): JsonResponse
    {
        return (new BankReconciliationResource($bankReconciliation->load([
            'account',
            'fiscalYear',
            'items.account',
            'items.journalEntryLine.journalEntry',
            'completedBy',
            'creator',
        ])))->response();
    }

    public function update(
        UpdateBankReconciliationRequest $request,
        BankReconciliation $bankReconciliation,
    ): JsonResponse {
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

        return (new BankReconciliationResource($bankReconciliation->refresh()->load([
            'account',
            'fiscalYear',
            'items.account',
            'items.journalEntryLine.journalEntry',
            'completedBy',
            'creator',
        ])))->response();
    }

    public function destroy(BankReconciliation $bankReconciliation): JsonResponse
    {
        $bankReconciliation->items()->delete();
        $bankReconciliation->delete();

        return response()->json(null, 204);
    }

    public function export(
        ExportBankReconciliationRequest $request,
        ExportBankReconciliationsAction $action,
    ): JsonResponse {
        return $action->execute($request);
    }

    public function complete(
        BankReconciliation $bankReconciliation,
        CompleteBankReconciliationAction $action,
    ): JsonResponse {
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

    public function importPreview(Request $request): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240']]);

        $result = (new PreviewBankStatementAction)->execute($request->file('file'));

        return response()->json($result);
    }

    public function importStatement(
        ImportBankStatementRequest $request,
        BankReconciliation $bankReconciliation,
    ): JsonResponse {
        $summary = (new ImportBankStatementAction)->execute(
            $bankReconciliation,
            $request->file('file'),
            $request->validated('mapping')
        );
        $bankReconciliation->recalculateBalances();

        return response()->json($summary);
    }

    public function autoMatch(
        BankReconciliation $bankReconciliation,
        AutoMatchBankReconciliationAction $action,
    ): JsonResponse {
        $summary = $action->execute($bankReconciliation);
        $bankReconciliation->recalculateBalances();

        return response()->json($summary);
    }

    public function matchItem(Request $request, BankReconciliation $bankReconciliation, int $item): JsonResponse
    {
        $validated = $request->validate([
            'journal_entry_line_id' => ['required', 'integer', 'exists:journal_entry_lines,id'],
        ]);

        /** @var BankReconciliationItem $bankItem */
        $bankItem = $bankReconciliation->items()->findOrFail($item);
        $result = (new MatchBankReconciliationItemAction)->execute($bankItem, $validated['journal_entry_line_id']);
        $bankReconciliation->recalculateBalances();

        return response()->json(['data' => $result]);
    }

    public function unmatchItem(BankReconciliation $bankReconciliation, int $item): JsonResponse
    {
        /** @var BankReconciliationItem $bankItem */
        $bankItem = $bankReconciliation->items()->findOrFail($item);
        $result = (new UnmatchBankReconciliationItemAction)->execute($bankItem);
        $bankReconciliation->recalculateBalances();

        return response()->json(['data' => $result]);
    }

    public function unmatchedJournalLines(Request $request, BankReconciliation $bankReconciliation): JsonResponse
    {
        $search = $request->query('search');
        $lines = (new GetUnmatchedJournalLinesAction)->execute($bankReconciliation, $search);

        return response()->json(['data' => $lines]);
    }

    public function assignItemAccount(Request $request, BankReconciliation $bankReconciliation, int $item): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
        ]);

        $bankItem = $bankReconciliation->items()->findOrFail($item);
        $bankItem->update(['account_id' => $validated['account_id']]);

        return response()->json(['data' => $bankItem->refresh()]);
    }
}
