<?php

namespace App\Http\Requests\AssetMovements;

class UpdateAssetMovementRequest extends AbstractAssetMovementRequest
{
    public function rules(): array
    {
        return $this->baseRules();
    }
}
