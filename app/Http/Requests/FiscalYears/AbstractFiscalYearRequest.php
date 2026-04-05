<?php

namespace App\Http\Requests\FiscalYears;

use App\Http\Requests\AuthorizedFormRequest;
use App\Http\Requests\Concerns\HasSometimesArrayRules;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

abstract class AbstractFiscalYearRequest extends AuthorizedFormRequest
{
    use HasSometimesArrayRules;

    public function rules(): array
    {
        return [
            'name' => $this->withSometimes(['required', 'string', 'max:255', $this->fiscalYearNameUniqueRule()]),
            'start_date' => $this->withSometimes(['required', 'date']),
            'end_date' => $this->withSometimes(['required', 'date', 'after:start_date']),
            'status' => $this->withSometimes(['required', 'in:open,closed,locked']),
        ];
    }

    protected function fiscalYearNameUniqueRule(): Unique
    {
        $rule = Rule::unique('fiscal_years', 'name');

        if (! $this->usesSometimes()) {
            return $rule;
        }

        return $rule->ignore($this->route('fiscal_year'));
    }

    abstract protected function usesSometimes(): bool;
}
