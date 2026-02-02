<?php

namespace App\Http\Resources\JournalEntries;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class JournalEntryCollection extends ResourceCollection
{
    public $collects = JournalEntryResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}
