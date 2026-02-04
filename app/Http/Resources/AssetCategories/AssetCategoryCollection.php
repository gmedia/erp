<?php

namespace App\Http\Resources\AssetCategories;

use App\Http\Resources\SimpleCrudCollection;

class AssetCategoryCollection extends SimpleCrudCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = AssetCategoryResource::class;
}
