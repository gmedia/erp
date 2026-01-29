<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property int $branch_id
 * @property int $quantity_on_hand
 * @property int $quantity_reserved
 * @property int $minimum_quantity
 * @property string $average_cost
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock query()
 *
 * @mixin \Eloquent
 */
class ProductStock extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'branch_id',
        'quantity_on_hand',
        'quantity_reserved',
        'minimum_quantity',
        'average_cost',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'average_cost' => 'decimal:2',
    ];

    /**
     * Get the product that this stock record belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the branch that this stock record belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the available quantity (on hand - reserved).
     */
    public function getQuantityAvailableAttribute(): int
    {
        return $this->quantity_on_hand - $this->quantity_reserved;
    }

    /**
     * Check if stock is below minimum quantity.
     */
    public function isBelowMinimum(): bool
    {
        return $this->quantity_on_hand < $this->minimum_quantity;
    }

    /**
     * Scope a query to only include low stock items.
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity_on_hand', '<', 'minimum_quantity');
    }
}
