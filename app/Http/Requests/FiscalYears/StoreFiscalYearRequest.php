<?php

namespace App\Http\Requests\FiscalYears;

class StoreFiscalYearRequest extends AbstractFiscalYearRequest
{
    protected function usesSometimes(): bool
    {
        return false;
    }
}
