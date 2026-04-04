<?php

namespace App\Http\Requests\AssetStocktakes;

class StoreAssetStocktakeRequest extends AbstractAssetStocktakeRequest
{
    protected function branchRules(): array
    {
        return [
            'branch_id' => ['required', 'exists:branches,id'],
        ];
    }

    protected function referenceStringRules(): array
    {
        return [
            'required',
            'string',
            'max:255',
        ];
    }

    protected function plannedAtRules(): array
    {
        return ['required', 'date'];
    }

    protected function statusRules(): array
    {
        return ['required', 'in:draft,in_progress,completed,cancelled'];
    }
}
