<?php

namespace App\Http\Controllers;

use App\Actions\AccountMappings\ExportAccountMappingsAction;
use App\Actions\AccountMappings\IndexAccountMappingsAction;
use App\Http\Controllers\Concerns\LoadsResourceRelations;
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
    use LoadsResourceRelations;

    public function index(IndexAccountMappingRequest $request, IndexAccountMappingsAction $action): JsonResponse
    {
        $items = $action->execute($request);

        return (new AccountMappingCollection($items))->response();
    }

    public function store(StoreAccountMappingRequest $request): JsonResponse
    {
        $mapping = AccountMapping::create($request->validated());

        return (new AccountMappingResource($this->loadResourceRelations($mapping)))
            ->response()
            ->setStatusCode(201);
    }

    public function show(AccountMapping $accountMapping): JsonResponse
    {
        return (new AccountMappingResource($this->loadResourceRelations($accountMapping)))->response();
    }

    public function update(UpdateAccountMappingRequest $request, AccountMapping $accountMapping): JsonResponse
    {
        $accountMapping->update($request->validated());

        return (new AccountMappingResource($this->loadResourceRelations($accountMapping)))->response();
    }

    public function destroy(AccountMapping $accountMapping): JsonResponse
    {
        return $this->destroyModel($accountMapping);
    }

    public function export(ExportAccountMappingRequest $request, ExportAccountMappingsAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    protected function resourceRelations(): array
    {
        return ['sourceAccount.coaVersion', 'targetAccount.coaVersion'];
    }
}
