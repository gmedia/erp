<?php

namespace App\Http\Requests\PipelineAuditTrail;

use Illuminate\Foundation\Http\FormRequest;

class ExportPipelineAuditTrailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'entity_type' => ['nullable', 'string'],
            'pipeline_id' => ['nullable', 'integer', 'exists:pipelines,id'],
            'from_state_id' => ['nullable', 'integer', 'exists:pipeline_states,id'],
            'to_state_id' => ['nullable', 'integer', 'exists:pipeline_states,id'],
            'performed_by' => ['nullable', 'integer', 'exists:users,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'sort_by' => ['nullable', 'string', 'in:created_at,entity_type,performed_by'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'format' => ['nullable', 'string', 'in:xlsx,csv'],
        ];
    }
}
