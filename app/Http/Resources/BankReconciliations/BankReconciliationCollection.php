<?php

namespace App\Http\Resources\BankReconciliations;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BankReconciliationCollection extends ResourceCollection
{
    public $collects = BankReconciliationResource::class;

    public function toArray(Request $request): array
    {
        return ['data' => $this->collection];
    }
}
