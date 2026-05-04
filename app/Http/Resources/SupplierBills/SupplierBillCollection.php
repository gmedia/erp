<?php

namespace App\Http\Resources\SupplierBills;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SupplierBillCollection extends ResourceCollection
{
    public $collects = SupplierBillResource::class;
}
