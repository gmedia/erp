<?php

namespace App\Exports;

use App\Exports\Concerns\SimpleCrudExport;
use App\Models\AssetCategory;

class AssetCategoryExport extends SimpleCrudExport
{
    protected function getModelClass(): string
    {
        return AssetCategory::class;
    }
}
