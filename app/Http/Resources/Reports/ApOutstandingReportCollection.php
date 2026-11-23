<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ApOutstandingReportCollection extends ResourceCollection
{
    public $collects = ApOutstandingReportResource::class;

    /**
     * @param  array{as_of_date?: string, totals?: array<string, string>}  $summary
     */
    public function __construct($resource, private readonly array $summary = [])
    {
        parent::__construct($resource);
    }

    public function toArray($request): array
    {
        return parent::toArray($request);
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
