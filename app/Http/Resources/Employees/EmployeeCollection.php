<?php

namespace App\Http\Resources\Employees;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|\Illuminate\Database\Eloquent\Model
 */
class EmployeeCollection extends ResourceCollection
{
    /**
     * The resource that this collection transforms.
     *
     * @var string
     */
    public $collects = EmployeeResource::class;
}
