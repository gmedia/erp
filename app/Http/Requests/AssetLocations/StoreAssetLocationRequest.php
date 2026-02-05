<?php

namespace App\Http\Requests\AssetLocations;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => 'required|exists:branches,id',
            'parent_id' => 'nullable|exists:asset_locations,id',
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
        ];
    }
}
