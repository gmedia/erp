<?php

namespace App\Models;

use App\Models\Concerns\BuildsAttributeCasts;
use Database\Factories\ArReceiptAllocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $ar_receipt_id
 * @property int $customer_invoice_id
 * @property numeric $allocated_amount
 * @property numeric $discount_given
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CustomerInvoice $customerInvoice
 * @property-read ArReceipt $receipt
 *
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
 *
 * @mixin \Eloquent
 */
class ArReceiptAllocation extends Model
{
    /** @use HasFactory<ArReceiptAllocationFactory> */
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
