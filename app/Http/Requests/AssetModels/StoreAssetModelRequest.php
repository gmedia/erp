<?php

namespace App\Http\Requests\AssetModels;

class StoreAssetModelRequest extends AbstractAssetModelRequest
{
    protected function usesSometimes(): bool
    {
        return false;
    }
}
