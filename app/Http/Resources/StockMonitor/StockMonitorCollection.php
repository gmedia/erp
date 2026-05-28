<?php

namespace App\Http\Resources\StockMonitor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|Model
 */
class StockMonitorCollection extends ResourceCollection
{
    /**
     * @var class-string
     */
    public $collects = StockMonitorResource::class;

    public function __construct($resource, private readonly array $summary = [])
    {
        parent::__construct($resource);
    }

    public function with(Request $request): array
    {
        return [
            'summary' => $this->summary,
        ];
    }
}
