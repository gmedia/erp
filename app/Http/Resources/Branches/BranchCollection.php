<?php

namespace App\Http\Resources\Branches;

use App\Http\Resources\SimpleCrudCollection;

class BranchCollection extends SimpleCrudCollection
{
    /**
     * @var class-string
     */
    public $collects = BranchResource::class;
}
