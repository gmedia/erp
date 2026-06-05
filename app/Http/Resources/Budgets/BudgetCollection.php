<?php

namespace App\Http\Resources\Budgets;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BudgetCollection extends ResourceCollection
{
    public $collects = BudgetResource::class;

    public function toArray(Request $request): array
    {
        return ['data' => $this->collection];
    }
}
