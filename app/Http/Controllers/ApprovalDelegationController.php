<?php

namespace App\Http\Controllers;

use App\Actions\ApprovalDelegations\ExportApprovalDelegationsAction;
use App\Actions\ApprovalDelegations\IndexApprovalDelegationsAction;
use App\DTOs\ApprovalDelegations\UpdateApprovalDelegationData;
use App\Exports\ApprovalDelegations\ApprovalDelegationExport;
use App\Http\Requests\ApprovalDelegations\ExportApprovalDelegationRequest;
use App\Http\Requests\ApprovalDelegations\IndexApprovalDelegationRequest;
use App\Http\Requests\ApprovalDelegations\StoreApprovalDelegationRequest;
use App\Http\Requests\ApprovalDelegations\UpdateApprovalDelegationRequest;
use App\Http\Resources\ApprovalDelegations\ApprovalDelegationCollection;
use App\Http\Resources\ApprovalDelegations\ApprovalDelegationResource;
use App\Models\ApprovalDelegation;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;

class ApprovalDelegationController extends Controller
{
    public function index(IndexApprovalDelegationRequest $request, IndexApprovalDelegationsAction $action): ApprovalDelegationCollection
    {
        $delegations = $action->execute($request->validated());
        return new ApprovalDelegationCollection($delegations);
    }

    public function store(StoreApprovalDelegationRequest $request): JsonResponse
    {
        $delegation = ApprovalDelegation::create($request->validated());
        $delegation->load(['delegator:id,name', 'delegate:id,name']);

        return response()->json([
            'message' => 'Approval delegation created successfully',
            'data' => new ApprovalDelegationResource($delegation),
        ], 201);
    }

    public function show(ApprovalDelegation $approvalDelegation): ApprovalDelegationResource
    {
        $approvalDelegation->load(['delegator:id,name', 'delegate:id,name']);
        return new ApprovalDelegationResource($approvalDelegation);
    }

    public function update(UpdateApprovalDelegationRequest $request, ApprovalDelegation $approvalDelegation): JsonResponse
    {
        $dto = UpdateApprovalDelegationData::fromArray($request->validated());
        
        $approvalDelegation->update($dto->toArray());
        $approvalDelegation->load(['delegator:id,name', 'delegate:id,name']);

        return response()->json([
            'message' => 'Approval delegation updated successfully',
            'data' => new ApprovalDelegationResource($approvalDelegation),
        ]);
    }

    public function destroy(ApprovalDelegation $approvalDelegation): JsonResponse
    {
        $approvalDelegation->delete();

        return response()->json(null, 204);
    }

    public function export(ExportApprovalDelegationRequest $request, ExportApprovalDelegationsAction $action): JsonResponse
    {
        return $action->execute($request->validated());
    }
}
