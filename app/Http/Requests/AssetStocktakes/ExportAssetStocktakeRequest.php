<?php

namespace App\Http\Requests\AssetStocktakes;

use App\Http\Requests\BaseListingRequest;

class ExportAssetStocktakeRequest extends BaseListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'branch' => ['nullable', 'exists:branches,id'],
                'status' => ['nullable', 'in:draft,in_progress,completed,cancelled'],
            ],
            $this->listingSortRules('id,reference,planned_at,performed_at,status,created_at,updated_at'),
        );
    }
}
