<?php

namespace App\Http\Requests\FiscalYears;

use App\Http\Requests\SimpleCrudExportRequest;

class ExportFiscalYearRequest extends SimpleCrudExportRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'status' => ['nullable', 'string', 'in:open,closed,locked'],
        ]);
    }
}
