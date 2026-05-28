<?php

namespace App\Models;

use Database\Factories\ProductionOrderFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $order_number
 * @property int $product_id
 * @property int|null $branch_id
 * @property numeric $quantity
 * @property int $unit_id
 * @property Carbon|null $planned_start_date
 * @property Carbon|null $planned_end_date
 * @property Carbon|null $actual_start_date
 * @property Carbon|null $actual_end_date
 * @property string $status
 * @property numeric $total_cost
 * @property string|null $notes
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Branch|null $branch
 * @property-read User|null $creator
 * @property-read Collection<int, ProductionOrderItem> $items
 * @property-read int|null $items_count
 * @property-read Product $product
 * @property-read Unit $unit
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder completed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder draft()
 * @method static \Database\Factories\ProductionOrderFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder inProgress()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereActualEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereActualStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereOrderNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder wherePlannedEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder wherePlannedStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereTotalCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ProductionOrder extends Model
{
    /** @use HasFactory<ProductionOrderFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_number',
        'product_id',
        'branch_id',
        'quantity',
        'unit_id',
        'planned_start_date',
        'planned_end_date',
        'actual_start_date',
        'actual_end_date',
        'status',
        'total_cost',
        'notes',
        'created_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductionOrderItem::class);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }
}
