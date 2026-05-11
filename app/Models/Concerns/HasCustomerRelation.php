<?php

namespace App\Models\Concerns;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasCustomerRelation
{
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
