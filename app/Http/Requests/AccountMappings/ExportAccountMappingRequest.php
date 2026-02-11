<?php

namespace App\Http\Requests\AccountMappings;

use App\Http\Requests\SimpleCrudExportRequest;

class ExportAccountMappingRequest extends SimpleCrudExportRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'type' => ['nullable', 'string', 'in:merge,split,rename'],
            'source_coa_version_id' => ['nullable', 'integer', 'exists:coa_versions,id'],
            'target_coa_version_id' => ['nullable', 'integer', 'exists:coa_versions,id'],
            'sort_by' => ['nullable', 'string', 'in:id,type,source,target,created_at,updated_at'],
        ]);
    }
}
