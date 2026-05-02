<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ArOutstandingReportCollection extends ResourceCollection
{
    public $collects = ArOutstandingReportResource::class;
}