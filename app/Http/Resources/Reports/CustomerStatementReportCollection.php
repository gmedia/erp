<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerStatementReportCollection extends ResourceCollection
{
    public $collects = CustomerStatementReportResource::class;
}
