<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property int $branch_id
 * @property numeric $quantity_on_hand
 * @property numeric $quantity_reserved
 * @property numeric $average_cost
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch $branch
 * @property-read float $quantity_available
 * @property-read \App\Models\Product $product
 *
 * @method static \Database\Factories\ProductStockFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock query()
 *
 * @mixin \Eloquent
 */
class ProductStock extends Model
{
    /** @use HasFactory<\Database\Factories\ProductStockFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'branch_id',
        'quantity_on_hand',
        'quantity_reserved',
        'average_cost',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'quantity_on_hand' => 'decimal:2',
        'quantity_reserved' => 'decimal:2',
        'average_cost' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function getQuantityAvailableAttribute(): float
    {
        return (float) $this->quantity_on_hand - (float) $this->quantity_reserved;
    }
}
