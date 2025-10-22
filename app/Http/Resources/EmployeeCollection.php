<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class EmployeeCollection extends ResourceCollection
{
    /**
     * The resource that this collection transforms.
     *
     * @var string
     */
    public $collects = EmployeeResource::class;
}
