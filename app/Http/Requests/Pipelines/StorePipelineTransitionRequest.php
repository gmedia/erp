<?php

namespace App\Http\Requests\Pipelines;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePipelineTransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_state_id' => [
                'required', 
                'exists:pipeline_states,id',
                Rule::unique('pipeline_transitions')->where(function ($query) {
                    return $query->where('pipeline_id', $this->route('pipeline')->id)
                                 ->where('to_state_id', $this->to_state_id);
                })
            ],
            'to_state_id' => ['required', 'exists:pipeline_states,id', 'different:from_state_id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'required_permission' => ['nullable', 'string', 'max:255'],
            'guard_conditions' => ['nullable', 'array'],
            'requires_confirmation' => ['boolean'],
            'requires_comment' => ['boolean'],
            'requires_approval' => ['boolean'],
            'sort_order' => ['integer'],
            'is_active' => ['boolean'],
            
            // Nested actions validation
            'actions' => ['nullable', 'array'],
            'actions.*.action_type' => ['required', 'string', Rule::in(['update_field', 'create_record', 'send_notification', 'dispatch_job', 'trigger_approval', 'webhook', 'custom'])],
            'actions.*.execution_order' => ['required', 'integer', 'min:1'],
            'actions.*.config' => ['required', 'array'],
            'actions.*.is_async' => ['boolean'],
            'actions.*.on_failure' => ['required', 'string', Rule::in(['abort', 'continue', 'log_and_continue'])],
            'actions.*.is_active' => ['boolean'],
        ];
    }
}
