<?php

namespace App\Http\Resources\InventoryStocktakes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|Model
 */
class InventoryStocktakeCollection extends ResourceCollection
{
    /**
     * @var string
     */
    public $collects = InventoryStocktakeResource::class;
}
