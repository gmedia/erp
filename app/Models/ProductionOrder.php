<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $order_number
 * @property int $product_id
 * @property int|null $branch_id
 * @property string $quantity_to_produce
 * @property \Illuminate\Support\Carbon $production_date
 * @property \Illuminate\Support\Carbon|null $completion_date
 * @property string $status
 * @property string $total_cost
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder query()
 *
 * @mixin \Eloquent
 */
class ProductionOrder extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_number',
        'product_id',
        'branch_id',
        'quantity_to_produce',
        'production_date',
        'completion_date',
        'status',
        'total_cost',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity_to_produce' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'production_date' => 'date',
        'completion_date' => 'date',
    ];

    /**
     * Get the product being produced.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the branch where production occurs.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the items (raw materials) used in this production order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProductionOrderItem::class);
    }

    /**
     * Scope a query to only include in-progress orders.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope a query to only include completed orders.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include draft orders.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }
}
