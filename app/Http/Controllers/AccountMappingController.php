<?php

namespace App\Http\Controllers;

use App\Actions\AccountMappings\ExportAccountMappingsAction;
use App\Actions\AccountMappings\IndexAccountMappingsAction;
use App\Http\Requests\AccountMappings\ExportAccountMappingRequest;
use App\Http\Requests\AccountMappings\IndexAccountMappingRequest;
use App\Http\Requests\AccountMappings\StoreAccountMappingRequest;
use App\Http\Requests\AccountMappings\UpdateAccountMappingRequest;
use App\Http\Resources\AccountMappings\AccountMappingCollection;
use App\Http\Resources\AccountMappings\AccountMappingResource;
use App\Models\AccountMapping;
use Illuminate\Http\JsonResponse;

class AccountMappingController extends Controller
{
    public function index(IndexAccountMappingRequest $request, IndexAccountMappingsAction $action): JsonResponse
    {
        $items = $action->execute($request);

        return (new AccountMappingCollection($items))->response();
    }

    public function store(StoreAccountMappingRequest $request): JsonResponse
    {
        $mapping = AccountMapping::create($request->validated());

        $mapping->load(['sourceAccount.coaVersion', 'targetAccount.coaVersion']);

        return (new AccountMappingResource($mapping))
            ->response()
            ->setStatusCode(201);
    }

    public function show(AccountMapping $accountMapping): JsonResponse
    {
        $accountMapping->load(['sourceAccount.coaVersion', 'targetAccount.coaVersion']);

        return (new AccountMappingResource($accountMapping))->response();
    }

    public function update(UpdateAccountMappingRequest $request, AccountMapping $accountMapping): JsonResponse
    {
        $accountMapping->update($request->validated());

        $accountMapping->load(['sourceAccount.coaVersion', 'targetAccount.coaVersion']);

        return (new AccountMappingResource($accountMapping))->response();
    }

    public function destroy(AccountMapping $accountMapping): JsonResponse
    {
        $accountMapping->delete();

        return response()->json(null, 204);
    }

    public function export(ExportAccountMappingRequest $request, ExportAccountMappingsAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
