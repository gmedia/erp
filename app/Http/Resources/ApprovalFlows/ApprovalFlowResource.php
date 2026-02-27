<?php

namespace App\Http\Resources\ApprovalFlows;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalFlowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'code' => $this->resource->code,
            'approvable_type' => $this->resource->approvable_type,
            'description' => $this->resource->description,
            'is_active' => $this->resource->is_active,
            'conditions' => $this->resource->conditions,
            'created_by' => $this->resource->created_by,
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->resource->creator->id,
                    'name' => $this->resource->creator->name,
                ];
            }),
            'steps' => $this->whenLoaded('steps', function () {
                return $this->resource->steps->map(function ($step) {
                    return [
                        'id' => $step->id,
                        'approval_flow_id' => $step->approval_flow_id,
                        'step_order' => $step->step_order,
                        'name' => $step->name,
                        'approver_type' => $step->approver_type,
                        'approver_user_id' => $step->approver_user_id,
                        'approver_role_id' => $step->approver_role_id,
                        'approver_department_id' => $step->approver_department_id,
                        'required_action' => $step->required_action,
                        'auto_approve_after_hours' => $step->auto_approve_after_hours,
                        'escalate_after_hours' => $step->escalate_after_hours,
                        'escalation_user_id' => $step->escalation_user_id,
                        'can_reject' => $step->can_reject,
                        'user' => $step->user ? ['id' => $step->user->id, 'name' => $step->user->name] : null,
                        'department' => $step->department ? ['id' => $step->department->id, 'name' => $step->department->name] : null,
                    ];
                });
            }),
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
