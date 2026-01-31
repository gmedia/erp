<?php

namespace App\Http\Requests\CoaVersions;

use App\Http\Requests\SimpleCrudIndexRequest;
use App\Models\CoaVersion;

class IndexCoaVersionRequest extends SimpleCrudIndexRequest
{
    protected function getModelClass(): string
    {
        return CoaVersion::class;
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'status' => ['nullable', 'string', 'in:draft,active,archived'],
            'fiscal_year_id' => ['nullable', 'integer', 'exists:fiscal_years,id'],
        ]);
    }
}
