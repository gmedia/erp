<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ApAgingReportCollection extends ResourceCollection
{
    public $collects = ApAgingReportResource::class;

    public function toArray($request): array
    {
        return parent::toArray($request);
    }
}
