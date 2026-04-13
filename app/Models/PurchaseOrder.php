<?php

namespace App\Models;

use App\Models\Concerns\BuildsAttributeCasts;
use App\Models\Concerns\HasSupplierRelation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use BuildsAttributeCasts, HasFactory, HasSupplierRelation;

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
                'created_at',
                'updated_at',
            ]),
        ];
    }
}
