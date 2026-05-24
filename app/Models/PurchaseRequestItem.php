<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $purchase_request_id
 * @property int $product_id
 * @property int $unit_id
 * @property numeric $quantity
 * @property numeric $quantity_ordered
 * @property numeric|null $estimated_unit_price
 * @property numeric|null $estimated_total
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\PurchaseRequest $purchaseRequest
 * @property-read \App\Models\Unit $unit
 *
 * @method static \Database\Factories\PurchaseRequestItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequestItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequestItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequestItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequestItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequestItem whereEstimatedTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequestItem whereEstimatedUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequestItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequestItem whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequestItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequestItem wherePurchaseRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequestItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequestItem whereQuantityOrdered($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequestItem whereUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequestItem whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PurchaseRequestItem extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseRequestItemFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'purchase_request_id',
        'product_id',
        'unit_id',
        'quantity',
        'quantity_ordered',
        'estimated_unit_price',
        'estimated_total',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'decimal:2',
        'quantity_ordered' => 'decimal:2',
        'estimated_unit_price' => 'decimal:2',
        'estimated_total' => 'decimal:2',
    ];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
