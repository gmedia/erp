<?php

namespace App\Models;

use App\Models\Concerns\BuildsAttributeCasts;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $ap_payment_id
 * @property int $supplier_bill_id
 * @property numeric $allocated_amount
 * @property numeric $discount_taken
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ApPayment $payment
 * @property-read \App\Models\SupplierBill $supplierBill
 *
 * @mixin \Eloquent
 */
class ApPaymentAllocation extends Model
{
    /** @use HasFactory<\Database\Factories\ApPaymentAllocationFactory> */
    use BuildsAttributeCasts, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ap_payment_id',
        'supplier_bill_id',
        'allocated_amount',
        'discount_taken',
        'notes',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(ApPayment::class, 'ap_payment_id');
    }

    public function supplierBill(): BelongsTo
    {
        return $this->belongsTo(SupplierBill::class);
    }

    protected function casts(): array
    {
        return [
            ...$this->decimalCasts([
                'allocated_amount',
                'discount_taken',
            ]),
        ];
    }
}
