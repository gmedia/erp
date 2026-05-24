<?php

namespace App\Models;

use App\Models\Concerns\BuildsAttributeCasts;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $ar_receipt_id
 * @property int $customer_invoice_id
 * @property numeric $allocated_amount
 * @property numeric $discount_given
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\CustomerInvoice $customerInvoice
 * @property-read \App\Models\ArReceipt $receipt
 * @method static \Database\Factories\ArReceiptAllocationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceiptAllocation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceiptAllocation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceiptAllocation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceiptAllocation whereAllocatedAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceiptAllocation whereArReceiptId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceiptAllocation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceiptAllocation whereCustomerInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceiptAllocation whereDiscountGiven($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceiptAllocation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceiptAllocation whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceiptAllocation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ArReceiptAllocation extends Model
{
    /** @use HasFactory<\Database\Factories\ArReceiptAllocationFactory> */
    use BuildsAttributeCasts, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ar_receipt_id',
        'customer_invoice_id',
        'allocated_amount',
        'discount_given',
        'notes',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(ArReceipt::class, 'ar_receipt_id');
    }

    public function customerInvoice(): BelongsTo
    {
        return $this->belongsTo(CustomerInvoice::class);
    }

    protected function casts(): array
    {
        return [
            ...$this->decimalCasts([
                'allocated_amount',
                'discount_given',
            ]),
        ];
    }
}
