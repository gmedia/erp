<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property int $warehouse_id
 * @property string $movement_type
 * @property string $quantity_in
 * @property string $quantity_out
 * @property string $balance_after
 * @property string|null $unit_cost
 * @property string|null $average_cost_after
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string|null $reference_number
 * @property string|null $notes
 * @property string $moved_at
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read Product $product
 * @property-read Warehouse $warehouse
 * @property-read User|null $createdBy
 *
 * @method static \Database\Factories\StockMovementFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class StockMovement extends Model
{
    /** @use HasFactory<\Database\Factories\StockMovementFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'movement_type',
        'quantity_in',
        'quantity_out',
        'balance_after',
        'unit_cost',
        'average_cost_after',
        'reference_type',
        'reference_id',
        'reference_number',
        'notes',
        'moved_at',
        'created_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'product_id' => 'integer',
        'warehouse_id' => 'integer',
        'quantity_in' => 'decimal:2',
        'quantity_out' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'average_cost_after' => 'decimal:2',
        'reference_id' => 'integer',
        'moved_at' => 'datetime',
        'created_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

