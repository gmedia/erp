<?php

namespace App\Http\Requests\AssetStocktakes;

use App\Http\Requests\BaseListingRequest;

class IndexAssetStocktakeRequest extends BaseListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'branch_id' => ['nullable', 'exists:branches,id'],
                'status' => ['nullable', 'in:draft,in_progress,completed,cancelled'],
            ],
            $this->listingSortRules('id,ulid,reference,branch,planned_at,performed_at,status,created_by,created_at,updated_at'),
            $this->paginationRules(),
        );
    }
}
