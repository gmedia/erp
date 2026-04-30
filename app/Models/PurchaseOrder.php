<?php

namespace App\Models;

use App\Models\Concerns\BuildsAttributeCasts;
use App\Models\Concerns\HasSupplierRelation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $po_number
 * @property int $supplier_id
 * @property int $warehouse_id
 * @property \Illuminate\Support\Carbon $order_date
 * @property \Illuminate\Support\Carbon|null $expected_delivery_date
 * @property string $payment_terms
 * @property string $currency
 * @property numeric $subtotal
 * @property numeric $tax_amount
 * @property numeric $discount_amount
 * @property numeric $grand_total
 * @property string $status
 * @property string|null $notes
 * @property string|null $shipping_address
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseOrderItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\Supplier $supplier
 * @property-read \App\Models\Warehouse $warehouse
 * @property-read \App\Models\User|null $approver
 * @property-read \App\Models\User|null $creator
 *
 * @mixin \Eloquent
 */
class PurchaseOrder extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseOrderFactory> */
    use BuildsAttributeCasts, HasFactory, HasSupplierRelation;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'po_number',
        'supplier_id',
        'warehouse_id',
        'order_date',
        'expected_delivery_date',
        'payment_terms',
        'currency',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'grand_total',
        'status',
        'notes',
        'shipping_address',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    protected function casts(): array
    {
        return [
            ...$this->dateCasts([
                'order_date',
                'expected_delivery_date',
            ]),
            ...$this->decimalCasts([
                'subtotal',
                'tax_amount',
                'discount_amount',
                'grand_total',
            ]),
            ...$this->datetimeCasts([
                'approved_at',
            ]),
        ];
    }
}
