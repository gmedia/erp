<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Resources\Json\ResourceCollection;

class GoodsReceiptReportCollection extends ResourceCollection
{
    public $collects = GoodsReceiptReportResource::class;
}
