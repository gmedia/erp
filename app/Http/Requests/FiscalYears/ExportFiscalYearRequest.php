<?php

namespace App\Http\Requests\FiscalYears;

class ExportFiscalYearRequest extends AbstractFiscalYearListingRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return $this->fiscalYearListingRules();
    }
}
