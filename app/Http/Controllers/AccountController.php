<?php

namespace App\Http\Controllers;

use App\Actions\Accounts\ExportAccountsAction;
use App\Actions\Accounts\IndexAccountsAction;
use App\Exceptions\ApiErrorException;
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
        if ($account->children()->count() > 0) {
            throw ApiErrorException::translated(422, 'account.has_child_accounts', 'account_api.has_child_accounts');
        }

        if ($account->journalLines()->count() > 0) {
            throw ApiErrorException::translated(422, 'account.has_journal_entries', 'account_api.has_journal_entries');
        }

        $account->delete();

        return response()->json(null, 204);
    }

    public function export(ExportAccountRequest $request, ExportAccountsAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
