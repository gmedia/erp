<?php

namespace App\Http\Requests\AssetModels;

class UpdateAssetModelRequest extends AbstractAssetModelRequest
{
    protected function usesSometimes(): bool
    {
        return true;
    }
}
