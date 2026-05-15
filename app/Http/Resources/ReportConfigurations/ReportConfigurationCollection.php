<?php

namespace App\Http\Resources\ReportConfigurations;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ReportConfigurationCollection extends ResourceCollection
{
    public $collects = ReportConfigurationResource::class;

    public function toArray(Request $request): array
    {
        return ['data' => $this->collection];
    }
}
