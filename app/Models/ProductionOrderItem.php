<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $production_order_id
 * @property int $product_id
 * @property numeric $quantity_planned
 * @property int $unit_id
 * @property numeric $quantity_used
 * @property numeric $unit_cost
 * @property numeric $cost
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\ProductionOrder $productionOrder
 * @property-read \App\Models\Unit $unit
 *
 * @method static \Database\Factories\ProductionOrderItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrderItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrderItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrderItem query()
 *
 * @mixin \Eloquent
 */
class ProductionOrderItem extends Model
{
    /** @use HasFactory<\Database\Factories\ProductionOrderItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'production_order_id',
        'product_id',
        'quantity_planned',
        'unit_id',
        'quantity_used',
        'unit_cost',
        'cost',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'quantity_planned' => 'decimal:2',
        'quantity_used' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'cost' => 'decimal:2',
    ];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
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
