<?php

namespace App\Http\Requests\AssetStocktakes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetStocktakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['sometimes', 'required', 'exists:branches,id'],
            'reference' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('asset_stocktakes')->where(function ($query) {
                    return $query->where('branch_id', $this->branch_id ?? $this->route('assetStocktake')->branch_id);
                })->ignore($this->route('assetStocktake')),
            ],
            'planned_at' => ['sometimes', 'required', 'date'],
            'performed_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'required', 'in:draft,in_progress,completed,cancelled'],
        ];
    }
}
