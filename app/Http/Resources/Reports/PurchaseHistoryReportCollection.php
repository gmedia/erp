<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PurchaseHistoryReportCollection extends ResourceCollection
{
    public $collects = PurchaseHistoryReportResource::class;

    /**
     * @param  array{as_of_date?: string, totals?: array<string, string>}  $summary
     */
    public function __construct($resource, private readonly array $summary = [])
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'summary' => $this->summary,
        ];
    }
}
