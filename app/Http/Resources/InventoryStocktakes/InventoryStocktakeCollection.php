<?php

namespace App\Http\Resources\InventoryStocktakes;

use Illuminate\Http\Resources\Json\ResourceCollection;

class InventoryStocktakeCollection extends ResourceCollection
{
    /**
     * @var string
     */
    public $collects = InventoryStocktakeResource::class;
}

