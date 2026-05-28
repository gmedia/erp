<?php

namespace App\Models;

use App\Models\Concerns\BuildsAttributeCasts;
use Database\Factories\ApPaymentAllocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $ap_payment_id
 * @property int $supplier_bill_id
 * @property numeric $allocated_amount
 * @property numeric $discount_taken
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ApPayment $payment
 * @property-read SupplierBill $supplierBill
 *
 * @method static \Database\Factories\ApPaymentAllocationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPaymentAllocation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPaymentAllocation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPaymentAllocation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPaymentAllocation whereAllocatedAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPaymentAllocation whereApPaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPaymentAllocation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPaymentAllocation whereDiscountTaken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPaymentAllocation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPaymentAllocation whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPaymentAllocation whereSupplierBillId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPaymentAllocation whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ApPaymentAllocation extends Model
{
    /** @use HasFactory<ApPaymentAllocationFactory> */
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
