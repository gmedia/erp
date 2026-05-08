<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ApPaymentHistoryReportCollection extends ResourceCollection
{
    public $collects = ApPaymentHistoryReportResource::class;

    public function toArray($request): array
    {
        return parent::toArray($request);
    }
}
