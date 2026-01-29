<?php

namespace App\Http\Resources\Units;

use App\Http\Resources\SimpleCrudCollection;

class UnitCollection extends SimpleCrudCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = UnitResource::class;
}
