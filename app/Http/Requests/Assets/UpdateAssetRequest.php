<?php

namespace App\Http\Requests\Assets;

class UpdateAssetRequest extends AbstractAssetRequest
{
    protected function usesSometimes(): bool
    {
        return true;
    }
}
