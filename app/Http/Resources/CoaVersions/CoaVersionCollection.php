<?php

namespace App\Http\Resources\CoaVersions;

use App\Http\Resources\SimpleCrudCollection;

class CoaVersionCollection extends SimpleCrudCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = CoaVersionResource::class;
}
