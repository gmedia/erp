<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PurchaseHistoryReportCollection extends ResourceCollection
{
    public $collects = PurchaseHistoryReportResource::class;
}
