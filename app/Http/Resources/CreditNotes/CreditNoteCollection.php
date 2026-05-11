<?php

namespace App\Http\Resources\CreditNotes;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CreditNoteCollection extends ResourceCollection
{
    public $collects = CreditNoteResource::class;
}
