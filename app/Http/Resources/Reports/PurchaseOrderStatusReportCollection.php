<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PurchaseOrderStatusReportCollection extends ResourceCollection
{
    public $collects = PurchaseOrderStatusReportResource::class;
}
