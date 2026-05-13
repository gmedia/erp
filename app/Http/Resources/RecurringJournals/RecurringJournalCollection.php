<?php

namespace App\Http\Resources\RecurringJournals;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class RecurringJournalCollection extends ResourceCollection
{
    public $collects = RecurringJournalResource::class;

    public function toArray(Request $request): array
    {
        return ['data' => $this->collection];
    }
}
