<?php

namespace App\Http\Requests\AssetMovements;

use Illuminate\Validation\Rule;

class UpdateAssetMovementRequest extends AbstractAssetMovementRequest
{
    public function rules(): array
    {
        return array_merge($this->baseRules(), [
            'asset_id' => ['sometimes', 'integer', 'exists:assets,id'],
            'movement_type' => [
                'sometimes',
                'string',
                Rule::in(['acquired', 'transfer', 'assign', 'return', 'dispose', 'adjustment']),
            ],
        ]);
    }
}
