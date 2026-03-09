<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $inventory_stocktake_id
 * @property int $product_id
 * @property int $unit_id
 * @property numeric $system_quantity
 * @property numeric|null $counted_quantity
 * @property numeric|null $variance
 * @property string $result
 * @property string|null $notes
 * @property int|null $counted_by
 * @property \Illuminate\Support\Carbon|null $counted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $countedBy
 * @property-read \App\Models\InventoryStocktake $inventoryStocktake
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\Unit $unit
 *
 * @method static \Database\Factories\InventoryStocktakeItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktakeItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktakeItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktakeItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktakeItem whereCountedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktakeItem whereCountedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktakeItem whereCountedQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktakeItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktakeItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktakeItem whereInventoryStocktakeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktakeItem whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktakeItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktakeItem whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktakeItem whereSystemQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktakeItem whereUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktakeItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktakeItem whereVariance($value)
 *
 * @mixin \Eloquent
 */
class InventoryStocktakeItem extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryStocktakeItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'inventory_stocktake_id',
        'product_id',
        'unit_id',
        'system_quantity',
        'counted_quantity',
        'variance',
        'result',
        'notes',
        'counted_by',
        'counted_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'system_quantity' => 'decimal:2',
        'counted_quantity' => 'decimal:2',
        'variance' => 'decimal:2',
        'counted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function inventoryStocktake(): BelongsTo
    {
        return $this->belongsTo(InventoryStocktake::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function countedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }
}
