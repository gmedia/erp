<?php

namespace App\Http\Requests\AssetStocktakes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetStocktakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'reference' => [
                'required',
                'string',
                'max:255',
                Rule::unique('asset_stocktakes')->where(function ($query) {
                    return $query->where('branch_id', $this->branch_id);
                }),
            ],
            'planned_at' => ['required', 'date'],
            'status' => ['required', 'in:draft,in_progress,completed,cancelled'],
        ];
    }
}
