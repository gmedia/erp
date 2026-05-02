<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ApOutstandingReportCollection extends ResourceCollection
{
    public $collects = ApOutstandingReportResource::class;

    public function toArray($request): array
    {
        return parent::toArray($request);
    }
}
