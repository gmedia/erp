<?php

namespace App\Http\Requests\FiscalYears;

use App\Http\Requests\SimpleCrudStoreRequest;
use App\Models\FiscalYear;

class StoreFiscalYearRequest extends SimpleCrudStoreRequest
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
        return [
            'name' => ['required', 'string', 'max:255', 'unique:fiscal_years,name'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['required', 'in:open,closed,locked'],
        ];
    }
}
