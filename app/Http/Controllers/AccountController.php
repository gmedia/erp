<?php

namespace App\Http\Controllers;

use App\Actions\Accounts\ExportAccountsAction;
use App\Actions\Accounts\IndexAccountsAction;
use App\Http\Requests\Accounts\ExportAccountRequest;
use App\Http\Requests\Accounts\IndexAccountRequest;
use App\Http\Requests\Accounts\StoreAccountRequest;
use App\Http\Requests\Accounts\UpdateAccountRequest;
use App\Http\Resources\Accounts\AccountCollection;
use App\Http\Resources\Accounts\AccountResource;
use App\Models\Account;
use Illuminate\Http\JsonResponse;

class AccountController extends Controller
{
    public function index(IndexAccountRequest $request, IndexAccountsAction $action): JsonResponse
    {
        $accounts = $action->execute($request);

        return (new AccountCollection($accounts))->response();
    }

    public function store(StoreAccountRequest $request): JsonResponse
    {
        $account = Account::create($request->validated());

        return (new AccountResource($account))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Account $account): JsonResponse
    {
        return (new AccountResource($account->load('parent')))->response();
    }

    public function update(UpdateAccountRequest $request, Account $account): JsonResponse
    {
        $account->update($request->validated());

        return (new AccountResource($account->load('parent')))->response();
    }

    public function destroy(Account $account): JsonResponse
    {
        // Optimization: check if has children or journal entries before delete
        if ($account->children()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete account with child accounts.',
            ], 422);
        }

        if ($account->journalLines()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete account with journal entries.',
            ], 422);
        }

        $account->delete();

        return response()->json(null, 204);
    }

    public function export(ExportAccountRequest $request, ExportAccountsAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
