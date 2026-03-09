<?php

namespace App\Http\Requests\CoaVersions;

use App\Http\Requests\SimpleCrudExportRequest;
use App\Models\CoaVersion;

class ExportCoaVersionRequest extends SimpleCrudExportRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'status' => ['nullable', 'string', 'in:draft,active,archived'],
            'fiscal_year_id' => ['nullable', 'integer', 'exists:fiscal_years,id'],
        ]);
    }

    protected function getModelClass(): string
    {
        return CoaVersion::class;
    }
}
