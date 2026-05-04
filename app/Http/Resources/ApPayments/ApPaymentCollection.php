<?php

namespace App\Http\Resources\ApPayments;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ApPaymentCollection extends ResourceCollection
{
    public $collects = ApPaymentResource::class;
}
