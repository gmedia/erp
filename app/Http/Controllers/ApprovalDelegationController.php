<?php

namespace App\Http\Controllers;

use App\Actions\ApprovalDelegations\ExportApprovalDelegationsAction;
use App\Actions\ApprovalDelegations\IndexApprovalDelegationsAction;
use App\DTOs\ApprovalDelegations\UpdateApprovalDelegationData;
use App\Http\Controllers\Concerns\LoadsResourceRelations;
use App\Http\Requests\ApprovalDelegations\ExportApprovalDelegationRequest;
use App\Http\Requests\ApprovalDelegations\IndexApprovalDelegationRequest;
use App\Http\Requests\ApprovalDelegations\StoreApprovalDelegationRequest;
use App\Http\Requests\ApprovalDelegations\UpdateApprovalDelegationRequest;
use App\Http\Resources\ApprovalDelegations\ApprovalDelegationCollection;
use App\Http\Resources\ApprovalDelegations\ApprovalDelegationResource;
use App\Models\ApprovalDelegation;
use Illuminate\Http\JsonResponse;

class ApprovalDelegationController extends Controller
{
    use LoadsResourceRelations;

    public function index(
        IndexApprovalDelegationRequest $request,
        IndexApprovalDelegationsAction $action,
    ): ApprovalDelegationCollection {
        $delegations = $action->execute($request->validated());

        return new ApprovalDelegationCollection($delegations);
    }

    public function store(StoreApprovalDelegationRequest $request): JsonResponse
    {
        $delegation = ApprovalDelegation::create($request->validated());

        return response()->json([
            'message' => 'Approval delegation created successfully',
            'data' => new ApprovalDelegationResource($this->loadResourceRelations($delegation)),
        ], 201);
    }

    public function show(ApprovalDelegation $approvalDelegation): ApprovalDelegationResource
    {
        return new ApprovalDelegationResource($this->loadResourceRelations($approvalDelegation));
    }

    public function update(
        UpdateApprovalDelegationRequest $request,
        ApprovalDelegation $approvalDelegation,
    ): JsonResponse {
        $dto = UpdateApprovalDelegationData::fromArray($request->validated());

        $approvalDelegation->update($dto->toArray());

        return response()->json([
            'message' => 'Approval delegation updated successfully',
            'data' => new ApprovalDelegationResource($this->loadResourceRelations($approvalDelegation)),
        ]);
    }

    public function destroy(ApprovalDelegation $approvalDelegation): JsonResponse
    {
        $approvalDelegation->delete();

        return response()->json(null, 204);
    }

    public function export(
        ExportApprovalDelegationRequest $request,
        ExportApprovalDelegationsAction $action,
    ): JsonResponse {
        return $action->execute($request->validated());
    }

    protected function resourceRelations(): array
    {
        return ['delegator:id,name', 'delegate:id,name'];
    }
}
