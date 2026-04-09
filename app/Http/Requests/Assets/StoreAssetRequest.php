<?php

namespace App\Http\Requests\Assets;

class StoreAssetRequest extends AbstractAssetRequest
{
    protected function usesSometimes(): bool
    {
        return false;
    }
}
