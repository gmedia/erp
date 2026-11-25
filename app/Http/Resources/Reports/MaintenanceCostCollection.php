<?php

namespace App\Http\Resources\Reports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|Model
 */
class MaintenanceCostCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var class-string
     */
    public $collects = MaintenanceCostResource::class;

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
