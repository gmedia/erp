<?php

namespace App\Http\Requests\AssetCategories;

class StoreAssetCategoryRequest extends AbstractAssetCategoryRequest
{
    protected function usesIgnoreRule(): bool
    {
        return false;
    }
}
