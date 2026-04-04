<?php

namespace App\Http\Requests\FiscalYears;

class UpdateFiscalYearRequest extends AbstractFiscalYearRequest
{
    protected function usesSometimes(): bool
    {
        return true;
    }
}
