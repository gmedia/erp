<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $stock_adjustment_id
 * @property int $product_id
 * @property int $unit_id
 * @property numeric $quantity_before
 * @property numeric $quantity_adjusted
 * @property numeric $quantity_after
 * @property numeric $unit_cost
 * @property numeric $total_cost
 * @property string|null $reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\StockAdjustment $stockAdjustment
 * @property-read \App\Models\Unit $unit
 *
 * @method static \Database\Factories\StockAdjustmentItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustmentItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustmentItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustmentItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustmentItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustmentItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustmentItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustmentItem whereQuantityAdjusted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustmentItem whereQuantityAfter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustmentItem whereQuantityBefore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustmentItem whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustmentItem whereStockAdjustmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustmentItem whereTotalCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustmentItem whereUnitCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustmentItem whereUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustmentItem whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class StockAdjustmentItem extends Model
{
    /** @use HasFactory<\Database\Factories\StockAdjustmentItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'stock_adjustment_id',
        'product_id',
        'unit_id',
        'quantity_before',
        'quantity_adjusted',
        'quantity_after',
        'unit_cost',
        'total_cost',
        'reason',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'quantity_before' => 'decimal:2',
        'quantity_adjusted' => 'decimal:2',
        'quantity_after' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class);
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
