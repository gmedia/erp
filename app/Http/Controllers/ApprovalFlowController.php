<?php

namespace App\Http\Controllers;

use App\Actions\ApprovalFlows\ExportApprovalFlowsAction;
use App\Actions\ApprovalFlows\IndexApprovalFlowsAction;
use App\DTOs\ApprovalFlows\UpdateApprovalFlowData;
use App\Http\Requests\ApprovalFlows\ExportApprovalFlowRequest;
use App\Http\Requests\ApprovalFlows\IndexApprovalFlowRequest;
use App\Http\Requests\ApprovalFlows\StoreApprovalFlowRequest;
use App\Http\Requests\ApprovalFlows\UpdateApprovalFlowRequest;
use App\Http\Resources\ApprovalFlows\ApprovalFlowCollection;
use App\Http\Resources\ApprovalFlows\ApprovalFlowResource;
use App\Models\ApprovalFlow;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ApprovalFlowController extends Controller
{
    public function index(IndexApprovalFlowRequest $request, IndexApprovalFlowsAction $action): ApprovalFlowCollection
    {
        return new ApprovalFlowCollection($action->execute($request));
    }

    public function store(StoreApprovalFlowRequest $request): ApprovalFlowResource
    {
        $flow = DB::transaction(function () use ($request) {
            $validated = $request->validated();

            $flow = ApprovalFlow::create([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'approvable_type' => $validated['approvable_type'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
                'conditions' => $validated['conditions'] ?? null,
                'created_by' => auth()->id(),
            ]);

            if (! empty($validated['steps'])) {
                foreach ($validated['steps'] as $index => $stepData) {
                    $flow->steps()->create([
                        'step_order' => $index + 1,
                        'name' => $stepData['name'],
                        'approver_type' => $stepData['approver_type'],
                        'approver_user_id' => $stepData['approver_user_id'] ?? null,
                        'approver_role_id' => $stepData['approver_role_id'] ?? null,
                        'approver_department_id' => $stepData['approver_department_id'] ?? null,
                        'required_action' => $stepData['required_action'],
                        'auto_approve_after_hours' => $stepData['auto_approve_after_hours'] ?? null,
                        'escalate_after_hours' => $stepData['escalate_after_hours'] ?? null,
                        'escalation_user_id' => $stepData['escalation_user_id'] ?? null,
                        'can_reject' => $stepData['can_reject'] ?? true,
                    ]);
                }
            }

            return $flow->load(['steps.user', 'steps.department', 'creator']);
        });

        return new ApprovalFlowResource($flow);
    }

    public function show(ApprovalFlow $approvalFlow): ApprovalFlowResource
    {
        return new ApprovalFlowResource($approvalFlow->load(['steps.user', 'steps.department', 'creator']));
    }

    public function update(UpdateApprovalFlowRequest $request, ApprovalFlow $approvalFlow): ApprovalFlowResource
    {
        $flow = DB::transaction(function () use ($request, $approvalFlow) {
            $validated = $request->validated();
            $dto = UpdateApprovalFlowData::fromArray($validated);

            $approvalFlow->update($dto->toArray());

            if (isset($validated['steps'])) {
                $approvalFlow->steps()->delete();

                foreach ($validated['steps'] as $index => $stepData) {
                    $approvalFlow->steps()->create([
                        'step_order' => $index + 1,
                        'name' => $stepData['name'],
                        'approver_type' => $stepData['approver_type'],
                        'approver_user_id' => $stepData['approver_user_id'] ?? null,
                        'approver_role_id' => $stepData['approver_role_id'] ?? null,
                        'approver_department_id' => $stepData['approver_department_id'] ?? null,
                        'required_action' => $stepData['required_action'],
                        'auto_approve_after_hours' => $stepData['auto_approve_after_hours'] ?? null,
                        'escalate_after_hours' => $stepData['escalate_after_hours'] ?? null,
                        'escalation_user_id' => $stepData['escalation_user_id'] ?? null,
                        'can_reject' => $stepData['can_reject'] ?? true,
                    ]);
                }
            }

            return $approvalFlow->fresh(['steps.user', 'steps.department', 'creator']);
        });

        return new ApprovalFlowResource($flow);
    }

    public function destroy(ApprovalFlow $approvalFlow): JsonResponse
    {
        return $this->destroyModel($approvalFlow);
    }

    public function export(ExportApprovalFlowRequest $request, ExportApprovalFlowsAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
