<?php

namespace App\Http\Requests\AssetStocktakes;

use App\Http\Requests\AuthorizedFormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

abstract class AbstractAssetStocktakeRequest extends AuthorizedFormRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->branchRules(),
            [
                'reference' => [
                    ...$this->referenceStringRules(),
                    $this->assetStocktakeReferenceUniqueRule(),
                ],
                'planned_at' => $this->plannedAtRules(),
                'status' => $this->statusRules(),
            ],
            $this->additionalRules(),
        );
    }

    /**
     * @return array<string, array<int, string>>
     */
    abstract protected function branchRules(): array;

    /**
     * @return array<int, string>
     */
    abstract protected function referenceStringRules(): array;

    /**
     * @return array<int, string>
     */
    abstract protected function plannedAtRules(): array;

    /**
     * @return array<int, string>
     */
    abstract protected function statusRules(): array;

    /**
     * @return array<string, array<int, string>>
     */
    protected function additionalRules(): array
    {
        return [];
    }

    protected function assetStocktakeReferenceUniqueRule(): Unique
    {
        return Rule::unique('asset_stocktakes')->where(function ($query) {
            return $query->where('branch_id', $this->assetStocktakeBranchId());
        });
    }

    protected function assetStocktakeBranchId(): mixed
    {
        return $this->branch_id;
    }
}
