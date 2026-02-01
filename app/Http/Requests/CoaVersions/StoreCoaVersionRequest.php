<?php

namespace App\Http\Requests\CoaVersions;

use App\Http\Requests\SimpleCrudStoreRequest;
use App\Models\CoaVersion;

use Illuminate\Validation\Rule;

class StoreCoaVersionRequest extends SimpleCrudStoreRequest
{
    protected function getModelClass(): string
    {
        return CoaVersion::class;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('coa_versions')->where(fn ($query) => $query->where('fiscal_year_id', $this->fiscal_year_id))
            ],
            'fiscal_year_id' => ['required', 'integer', 'exists:fiscal_years,id'],
            'status' => ['required', 'string', 'in:draft,active,archived'],
        ];
    }
}
