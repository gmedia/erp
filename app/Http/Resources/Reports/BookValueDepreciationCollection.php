<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|\Illuminate\Database\Eloquent\Model
 */
class BookValueDepreciationCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var class-string
     */
    public $collects = BookValueDepreciationResource::class;

}
