<?php

namespace App\Http\Requests\AssetCategories;

class UpdateAssetCategoryRequest extends AbstractAssetCategoryRequest
{
    protected function usesIgnoreRule(): bool
    {
        return true;
    }
}
