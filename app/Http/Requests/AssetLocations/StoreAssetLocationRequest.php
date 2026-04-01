<?php

namespace App\Http\Requests\AssetLocations;

class StoreAssetLocationRequest extends AbstractAssetLocationRequest
{
    protected function usesSometimes(): bool
    {
        return false;
    }
}
