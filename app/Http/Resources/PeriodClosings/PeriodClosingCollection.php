<?php

namespace App\Http\Resources\PeriodClosings;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PeriodClosingCollection extends ResourceCollection
{
    public $collects = PeriodClosingResource::class;

    public function toArray(Request $request): array
    {
        return ['data' => $this->collection];
    }
}
