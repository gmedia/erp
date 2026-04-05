<?php

namespace App\Http\Requests\FiscalYears;

class IndexFiscalYearRequest extends AbstractFiscalYearListingRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge(
            $this->fiscalYearListingRules('id,name,start_date,end_date,status,created_at,updated_at'),
            $this->paginationRules(),
        );
    }
}
