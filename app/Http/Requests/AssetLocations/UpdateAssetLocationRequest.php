<?php

namespace App\Http\Requests\AssetLocations;

class UpdateAssetLocationRequest extends AbstractAssetLocationRequest
{
    protected function usesSometimes(): bool
    {
        return true;
    }
}
