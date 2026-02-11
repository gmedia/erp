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
        $rules = parent::rules();
        $rules['sort_by'] = [
            'nullable',
            'string',
            'in:id,name,fiscal_year_id,fiscal_year.name,fiscal_year_name,status,created_at,updated_at',
        ];

        return array_merge($rules, [
            'status' => ['nullable', 'string', 'in:draft,active,archived'],
            'fiscal_year_id' => ['nullable', 'integer', 'exists:fiscal_years,id'],
        ]);
    }
}
