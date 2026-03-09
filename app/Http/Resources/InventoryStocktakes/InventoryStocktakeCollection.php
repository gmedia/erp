<?php

namespace App\Http\Resources\InventoryStocktakes;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|\Illuminate\Database\Eloquent\Model
 */
class InventoryStocktakeCollection extends ResourceCollection
{
    /**
     * @var string
     */
    public $collects = InventoryStocktakeResource::class;
}
