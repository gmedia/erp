<?php

namespace App\Http\Resources\CustomerInvoices;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerInvoiceCollection extends ResourceCollection
{
    public $collects = CustomerInvoiceResource::class;
}
