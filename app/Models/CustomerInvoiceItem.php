<?php

namespace App\Models;

use App\Models\Concerns\BuildsAttributeCasts;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $customer_invoice_id
 * @property int|null $product_id
 * @property int $account_id
 * @property string $description
 * @property numeric $quantity
 * @property int|null $unit_id
 * @property numeric $unit_price
 * @property numeric $discount_percent
 * @property numeric $tax_percent
 * @property numeric $line_total
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\CustomerInvoice $customerInvoice
 * @property-read \App\Models\Product|null $product
 * @property-read \App\Models\Account $account
 * @property-read \App\Models\Unit|null $unit
 *
 * @mixin \Eloquent
 */
class CustomerInvoiceItem extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerInvoiceItemFactory> */
    use BuildsAttributeCasts, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_invoice_id',
        'product_id',
        'account_id',
        'description',
        'quantity',
        'unit_id',
        'unit_price',
        'discount_percent',
        'tax_percent',
        'line_total',
        'notes',
    ];

    public function customerInvoice(): BelongsTo
    {
        return $this->belongsTo(CustomerInvoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    protected function casts(): array
    {
        return [
            ...$this->decimalCasts([
                'quantity',
                'unit_price',
                'discount_percent',
                'tax_percent',
                'line_total',
            ]),
        ];
    }
}
