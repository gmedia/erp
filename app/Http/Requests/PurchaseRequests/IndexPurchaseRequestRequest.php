<?php

namespace App\Http\Requests\PurchaseRequests;

use Illuminate\Foundation\Http\FormRequest;

class IndexPurchaseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
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
            'estimated_amount_min' => ['nullable', 'numeric', 'min:0'],
            'estimated_amount_max' => ['nullable', 'numeric', 'min:0'],
            'sort_by' => [
                'nullable',
                'string',
                'in:id,pr_number,branch,branch_id,department,department_id,requester,requested_by,request_date,'
                    . 'required_date,priority,status,estimated_amount,created_at,updated_at',
            ],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
