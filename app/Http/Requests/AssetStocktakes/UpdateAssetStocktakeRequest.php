<?php

namespace App\Http\Requests\AssetStocktakes;

class UpdateAssetStocktakeRequest extends AbstractAssetStocktakeRequest
{
    protected function branchRules(): array
    {
        return [
            'branch_id' => ['sometimes', 'required', 'exists:branches,id'],
        ];
    }

    protected function referenceStringRules(): array
    {
        return [
            'sometimes',
            'required',
            'string',
            'max:255',
        ];
    }

    protected function plannedAtRules(): array
    {
        return ['sometimes', 'required', 'date'];
    }

    protected function statusRules(): array
    {
        return ['sometimes', 'required', 'in:draft,in_progress,completed,cancelled'];
    }

    protected function additionalRules(): array
    {
        return [
            'performed_at' => ['nullable', 'date'],
        ];
    }

    protected function assetStocktakeReferenceUniqueRule(): \Illuminate\Validation\Rules\Unique
    {
        return parent::assetStocktakeReferenceUniqueRule()->ignore($this->route('asset_stocktake'));
    }

    protected function assetStocktakeBranchId(): mixed
    {
        return $this->input('branch_id') ?? $this->route('asset_stocktake')->branch_id;
    }
}
