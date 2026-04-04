<?php

namespace App\Http\Requests\CoaVersions;

use App\Http\Requests\Concerns\HasSometimesArrayRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

abstract class AbstractCoaVersionRequest extends FormRequest
{
    use HasSometimesArrayRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => $this->withSometimes([
                'required',
                'string',
                'max:255',
                $this->coaVersionNameUniqueRule(),
            ]),
            'fiscal_year_id' => $this->withSometimes(['required', 'integer', 'exists:fiscal_years,id']),
            'status' => $this->withSometimes(['required', 'string', 'in:draft,active,archived']),
        ];
    }

    protected function coaVersionNameUniqueRule(): Unique
    {
        $rule = Rule::unique('coa_versions', 'name')->where(
            fn ($query) => $query->where('fiscal_year_id', $this->input('fiscal_year_id'))
        );

        if (! $this->usesSometimes()) {
            return $rule;
        }

        return $rule->ignore($this->route('coa_version'));
    }

    abstract protected function usesSometimes(): bool;
}
