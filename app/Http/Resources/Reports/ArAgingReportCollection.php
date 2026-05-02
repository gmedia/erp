<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ArAgingReportCollection extends ResourceCollection
{
    public $collects = ArAgingReportResource::class;
}