<?php

namespace App\Http\Resources\Reports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|Model
 */
class StockMovementReportCollection extends ResourceCollection
{
    /**
     * @var class-string
     */
    public $collects = StockMovementReportResource::class;
}
