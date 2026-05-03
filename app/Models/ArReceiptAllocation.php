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
 * @property-read \App\Models\ArReceipt $receipt
 * @property-read \App\Models\CustomerInvoice $customerInvoice
 *
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
