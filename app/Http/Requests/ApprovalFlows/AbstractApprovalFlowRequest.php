<?php

namespace App\Http\Requests\ApprovalFlows;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class AbstractApprovalFlowRequest extends FormRequest
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
        $rules = [
            'name' => $this->withSometimes('required|string|max:255'),
            'code' => $this->codeRules(),
            'approvable_type' => $this->withSometimes('required|string|max:255'),
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'conditions' => 'nullable|array',
            'steps' => $this->withSometimes('required|array|min:1'),
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

        if ($this->includeStepIdRule()) {
            $rules['steps.*.id'] = 'nullable|exists:approval_flow_steps,id';
        }

        return $rules;
    }

    /**
     * @return array<int, string|Rule>|string
     */
    abstract protected function codeRules(): array|string;

    abstract protected function includeStepIdRule(): bool;

    abstract protected function useSometimes(): bool;

    private function withSometimes(string $rule): string
    {
        if (! $this->useSometimes()) {
            return $rule;
        }

        return 'sometimes|' . $rule;
    }
}
