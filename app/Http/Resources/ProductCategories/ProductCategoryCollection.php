<?php

namespace App\Http\Resources\ProductCategories;

use App\Http\Resources\SimpleCrudCollection;

class ProductCategoryCollection extends SimpleCrudCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = ProductCategoryResource::class;
}
