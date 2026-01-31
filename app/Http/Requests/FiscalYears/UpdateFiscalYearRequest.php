<?php

namespace App\Http\Requests\FiscalYears;

use App\Http\Requests\SimpleCrudUpdateRequest;
use App\Models\FiscalYear;

class UpdateFiscalYearRequest extends SimpleCrudUpdateRequest
{
    protected function getModelClass(): string
    {
        return FiscalYear::class;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $fiscalYear = $this->route('fiscal_year');
        $id = $fiscalYear instanceof FiscalYear ? $fiscalYear->id : $fiscalYear;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', 'unique:fiscal_years,name,' . $id],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date', 'after:start_date'],
            'status' => ['sometimes', 'required', 'in:open,closed,locked'],
        ];
    }
}
