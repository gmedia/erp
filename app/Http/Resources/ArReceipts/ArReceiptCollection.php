<?php

namespace App\Http\Resources\ArReceipts;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ArReceiptCollection extends ResourceCollection
{
    public $collects = ArReceiptResource::class;
}
