<?php

namespace App\Http\Requests\AssetLocations;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssetLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => 'sometimes|required|exists:branches,id',
            'parent_id' => 'sometimes|nullable|exists:asset_locations,id',
            'code' => 'sometimes|required|string|max:50',
            'name' => 'sometimes|required|string|max:255',
        ];
    }
}
