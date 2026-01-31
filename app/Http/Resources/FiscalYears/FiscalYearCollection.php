<?php

namespace App\Http\Resources\FiscalYears;

use App\Http\Resources\SimpleCrudCollection;

class FiscalYearCollection extends SimpleCrudCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = FiscalYearResource::class;
}
