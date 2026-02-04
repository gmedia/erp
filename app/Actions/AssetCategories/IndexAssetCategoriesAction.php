<?php

namespace App\Actions\AssetCategories;

use App\Actions\Concerns\SimpleCrudIndexAction;
use App\Models\AssetCategory;

class IndexAssetCategoriesAction extends SimpleCrudIndexAction
{
    protected function getModelClass(): string
    {
        return AssetCategory::class;
    }

    protected function getSearchFields(): array
    {
        return ['name', 'code'];
    }

    protected function getSortableFields(): array
    {
        return ['id', 'name', 'code', 'useful_life_months_default', 'created_at', 'updated_at'];
    }
}
