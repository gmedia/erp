<?php

namespace App\Http\Requests\CoaVersions;

use App\Http\Requests\SimpleCrudStoreRequest;
use App\Models\CoaVersion;

class StoreCoaVersionRequest extends SimpleCrudStoreRequest
{
    protected function getModelClass(): string
    {
        return CoaVersion::class;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'fiscal_year_id' => ['required', 'integer', 'exists:fiscal_years,id'],
            'status' => ['required', 'string', 'in:draft,active,archived'],
        ];
    }
}
