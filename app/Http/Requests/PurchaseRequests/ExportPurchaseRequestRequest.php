<?php

namespace App\Http\Requests\PurchaseRequests;

use Illuminate\Foundation\Http\FormRequest;

class ExportPurchaseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'branch' => ['nullable', 'integer', 'exists:branches,id'],
            'department' => ['nullable', 'integer', 'exists:departments,id'],
            'requested_by' => ['nullable', 'integer', 'exists:employees,id'],
            'priority' => ['nullable', 'string', 'in:low,normal,high,urgent'],
            'status' => [
                'nullable',
                'string',
                'in:draft,pending_approval,approved,rejected,partially_ordered,fully_ordered,cancelled',
            ],
            'request_date_from' => ['nullable', 'date'],
            'request_date_to' => ['nullable', 'date', 'after_or_equal:request_date_from'],
            'required_date_from' => ['nullable', 'date'],
            'required_date_to' => ['nullable', 'date', 'after_or_equal:required_date_from'],
            'sort_by' => [
                'nullable',
                'string',
                'in:pr_number,request_date,required_date,priority,status,estimated_amount,created_at',
            ],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
