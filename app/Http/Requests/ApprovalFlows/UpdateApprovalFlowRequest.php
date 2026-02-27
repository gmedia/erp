<?php

namespace App\Http\Requests\ApprovalFlows;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApprovalFlowRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'code' => ['sometimes', 'required', 'string', 'max:255', \Illuminate\Validation\Rule::unique('approval_flows')->ignore($this->approval_flow)],
            'approvable_type' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'conditions' => 'nullable|array',
            'steps' => 'sometimes|required|array|min:1',
            'steps.*.id' => 'nullable|exists:approval_flow_steps,id',
            'steps.*.name' => 'required|string|max:255',
            'steps.*.approver_type' => 'required|in:user,role,department_head',
            'steps.*.approver_user_id' => 'required_if:steps.*.approver_type,user|nullable|exists:users,id',
            'steps.*.approver_role_id' => 'required_if:steps.*.approver_type,role|nullable|integer',
            'steps.*.approver_department_id' => 'required_if:steps.*.approver_type,department_head|nullable|exists:departments,id',
            'steps.*.required_action' => 'required|in:approve,review,acknowledge',
            'steps.*.auto_approve_after_hours' => 'nullable|integer|min:0',
            'steps.*.escalate_after_hours' => 'nullable|integer|min:0',
            'steps.*.escalation_user_id' => 'nullable|exists:users,id',
            'steps.*.can_reject' => 'boolean',
        ];
    }
}
