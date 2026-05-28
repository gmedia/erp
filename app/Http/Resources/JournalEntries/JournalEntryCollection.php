<?php

namespace App\Http\Resources\JournalEntries;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|Model
 */
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
