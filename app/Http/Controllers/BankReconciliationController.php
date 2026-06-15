<?php

namespace App\Http\Controllers;

use App\Actions\BankReconciliations\AddBankReconciliationItemAction;
use App\Actions\BankReconciliations\AssignBankReconciliationItemAccountAction;
use App\Actions\BankReconciliations\AutoMatchBankReconciliationAction;
use App\Actions\BankReconciliations\CompleteBankReconciliationAction;
use App\Actions\BankReconciliations\DestroyBankReconciliationAction;
use App\Actions\BankReconciliations\ExportBankReconciliationsAction;
use App\Actions\BankReconciliations\GetUnmatchedJournalLinesAction;
use App\Actions\BankReconciliations\ImportBankStatementAction;
use App\Actions\BankReconciliations\IndexBankReconciliationsAction;
use App\Actions\BankReconciliations\MatchBankReconciliationItemAction;
use App\Actions\BankReconciliations\PreviewBankStatementAction;
use App\Actions\BankReconciliations\StoreBankReconciliationAction;
use App\Actions\BankReconciliations\UnmatchBankReconciliationItemAction;
use App\Actions\BankReconciliations\UpdateBankReconciliationAction;
use App\Http\Controllers\Concerns\LoadsResourceRelations;
use App\Http\Requests\BankReconciliations\AddBankReconciliationItemRequest;
use App\Http\Requests\BankReconciliations\AssignBankReconciliationItemAccountRequest;
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
    use LoadsResourceRelations;

    public function index(IndexBankReconciliationRequest $request, IndexBankReconciliationsAction $action): JsonResponse
    {
        return (new BankReconciliationCollection($action->execute($request)))->response();
    }

    public function store(StoreBankReconciliationRequest $request, StoreBankReconciliationAction $action): JsonResponse
    {
        $bankReconciliation = $action->execute($request);

        /** @var BankReconciliation $loaded */
        $loaded = $this->loadResourceRelations($bankReconciliation);

        return (new BankReconciliationResource($loaded))->response()->setStatusCode(201);
    }

    public function show(BankReconciliation $bankReconciliation): JsonResponse
    {
        /** @var BankReconciliation $loaded */
        $loaded = $this->loadResourceRelations($bankReconciliation);

        return (new BankReconciliationResource($loaded))->response();
    }

    public function update(
        UpdateBankReconciliationRequest $request,
        BankReconciliation $bankReconciliation,
        UpdateBankReconciliationAction $action,
    ): JsonResponse {
        $bankReconciliation = $action->execute($request, $bankReconciliation);

        /** @var BankReconciliation $loaded */
        $loaded = $this->loadResourceRelations($bankReconciliation);

        return (new BankReconciliationResource($loaded))->response();
    }

    public function destroy(
        BankReconciliation $bankReconciliation,
        DestroyBankReconciliationAction $action,
    ): JsonResponse {
        $action->execute($bankReconciliation);

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

    public function addItem(
        AddBankReconciliationItemRequest $request,
        BankReconciliation $bankReconciliation,
        AddBankReconciliationItemAction $action,
    ): JsonResponse {
        $item = $action->execute($request, $bankReconciliation);

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

    public function assignItemAccount(
        AssignBankReconciliationItemAccountRequest $request,
        BankReconciliation $bankReconciliation,
        int $item,
        AssignBankReconciliationItemAccountAction $action,
    ): JsonResponse {
        $bankItem = $action->execute($request, $bankReconciliation, $item);

        return response()->json(['data' => $bankItem]);
    }

    /**
     * @return array<int, string>
     */
    protected function resourceRelations(): array
    {
        return [
            'account',
            'fiscalYear',
            'items.account',
            'items.journalEntryLine.journalEntry',
            'completedBy',
            'creator',
        ];
    }
}
