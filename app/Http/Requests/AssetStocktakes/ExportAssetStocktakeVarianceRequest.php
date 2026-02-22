<?php

namespace App\Http\Requests\AssetStocktakes;

use Illuminate\Foundation\Http\FormRequest;

class ExportAssetStocktakeVarianceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'asset_stocktake_id' => ['nullable', 'exists:asset_stocktakes,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'result' => ['nullable', 'in:missing,damaged,moved'],
            'sort_by' => ['nullable', 'string', 'in:id,asset_code,asset_name,expected_branch,expected_location,found_branch,found_location,result,checked_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
