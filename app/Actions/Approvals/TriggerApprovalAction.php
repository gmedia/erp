<?php

namespace App\Actions\Approvals;

use App\Models\ApprovalAuditLog;
use App\Models\ApprovalFlow;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStep;
use App\Traits\HandlesConditions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TriggerApprovalAction
{
    use HandlesConditions;

    /**
     * Trigger an approval flow for a given entity.
     *
     * @param  Model  $entity  The entity to be approved
     * @param  array  $params  Configuration parameters (e.g., flow_code)
     */
    public function execute(Model $entity, array $params): ?ApprovalRequest
    {
        $flow = $this->resolveFlow($entity, $params);

        if (! $flow) {
            $this->logFlowWarning('No matching approval flow found for entity', $entity);

            return null;
        }

        $flow->loadMissing('steps');

        if ($flow->steps->isEmpty()) {
            $this->logFlowWarning(
                'Approval flow has no steps and cannot be triggered. Flow ID: '.$flow->id,
                $entity,
            );

            return null;
        }

        return DB::transaction(function () use ($entity, $flow) {
            // 1. Create the Approval Request
            $request = ApprovalRequest::create([
                'approval_flow_id' => $flow->id,
                'approvable_type' => $entity->getMorphClass(),
                'approvable_id' => $entity->getKey(),
                'current_step_order' => 1,
                'status' => 'pending',
                'submitted_by' => Auth::id(),
                'submitted_at' => now(),
            ]);

            // 2. Create the Request Steps based on the Flow Steps
            foreach ($flow->steps as $flowStep) {
                ApprovalRequestStep::create([
                    'approval_request_id' => $request->id,
                    'approval_flow_step_id' => $flowStep->id,
                    'step_order' => $flowStep->step_order,
                    'status' => 'pending',
                ]);
            }

            // 3. Log the audit event
            ApprovalAuditLog::create([
                'approval_request_id' => $request->id,
                'approvable_type' => $request->approvable_type,
                'approvable_id' => $request->approvable_id,
                'event' => 'submitted',
                'actor_user_id' => Auth::id(),
                'metadata' => json_encode([
                    'flow_id' => $flow->id,
                    'flow_code' => $flow->code,
                    'total_steps' => $flow->steps->count(),
                ]),
            ]);

            return $request;
        });
    }

    /**
     * Resolve the appropriate approval flow.
     */
    private function resolveFlow(Model $entity, array $params): ?ApprovalFlow
    {
        $entityType = $entity->getMorphClass();

        // 1. If flow_code is explicitly provided, use it
        if (isset($params['flow_code'])) {
            return ApprovalFlow::where('approvable_type', $entityType)
                ->where('code', $params['flow_code'])
                ->where('is_active', true)
                ->first();
        }

        // 2. Otherwise, find all active flows for this entity type
        $flows = ApprovalFlow::where('approvable_type', $entityType)
            ->where('is_active', true)
            ->orderBy('id', 'desc') // Check newer flows first
            ->get();

        foreach ($flows as $flow) {
            $conditions = $flow->conditions;

            // If no conditions, it's a default flow
            if (empty($conditions)) {
                return $flow;
            }

            // Evaluate conditions
            if ($this->evaluateConditions($conditions, $entity)) {
                return $flow;
            }
        }

        return null;
    }

    private function logFlowWarning(string $message, Model $entity): void
    {
        Log::warning($message.'. '.$this->buildEntityContext($entity));
    }

    private function buildEntityContext(Model $entity): string
    {
        return 'Entity: '.get_class($entity).' ID: '.$entity->getKey();
    }
}
