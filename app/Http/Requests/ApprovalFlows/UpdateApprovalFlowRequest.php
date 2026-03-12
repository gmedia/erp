<?php

namespace App\Http\Requests\ApprovalFlows;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'code' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('approval_flows')->ignore($this->approval_flow)],
            'approvable_type' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'conditions' => 'nullable|array',
            'steps' => 'sometimes|required|array|min:1',
            'steps.*.id' => 'nullable|exists:approval_flow_steps,id',
            'steps.*.name' => 'required|string|max:255',
            'steps.*.approver_type' => 'required|in:user',
            'steps.*.approver_user_id' => 'required|exists:users,id',
            'steps.*.approver_role_id' => 'prohibited',
            'steps.*.approver_department_id' => 'prohibited',
            'steps.*.required_action' => 'required|in:approve,review,acknowledge',
            'steps.*.auto_approve_after_hours' => 'nullable|integer|min:0',
            'steps.*.escalate_after_hours' => 'nullable|integer|min:0',
            'steps.*.escalation_user_id' => 'nullable|exists:users,id',
            'steps.*.can_reject' => 'boolean',
        ];
    }
}
