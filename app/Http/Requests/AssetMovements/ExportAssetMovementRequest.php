<?php

namespace App\Http\Requests\AssetMovements;

use App\Http\Requests\BaseListingRequest;

class ExportAssetMovementRequest extends BaseListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'asset_id' => ['nullable', 'exists:assets,id'],
                'movement_type' => ['nullable', 'string', 'in:acquired,transfer,assign,return,dispose,adjustment'],
                'from_branch_id' => ['nullable', 'exists:branches,id'],
                'to_branch_id' => ['nullable', 'exists:branches,id'],
                'from_employee_id' => ['nullable', 'exists:employees,id'],
                'to_employee_id' => ['nullable', 'exists:employees,id'],
                'start_date' => ['nullable', 'date'],
                'end_date' => ['nullable', 'date'],
            ],
            $this->listingSortRules('moved_at,movement_type,created_at'),
        );
    }
}
