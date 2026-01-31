<?php

namespace App\Http\Requests\FiscalYears;

use App\Http\Requests\SimpleCrudIndexRequest;

class IndexFiscalYearRequest extends SimpleCrudIndexRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'status' => ['nullable', 'string', 'in:open,closed,locked'],
            'sort_by' => ['nullable', 'string', 'in:id,name,start_date,end_date,status,created_at,updated_at'],
        ]);
    }
}
