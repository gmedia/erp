<?php

namespace App\Http\Requests\AssetLocations;

use App\Http\Requests\BaseListingRequest;

class ExportAssetLocationRequest extends BaseListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'branch_id' => ['nullable', 'exists:branches,id'],
                'parent_id' => ['nullable', 'exists:asset_locations,id'],
            ],
            $this->listingSortRules('code,name,branch,parent,created_at,updated_at'),
        );
    }
}
