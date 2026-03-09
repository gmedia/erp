<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string|null $stocktake_number
 * @property int $warehouse_id
 * @property \Illuminate\Support\Carbon $stocktake_date
 * @property string $status
 * @property int|null $product_category_id
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $completed_by
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $completedBy
 * @property-read \App\Models\User|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\InventoryStocktakeItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\ProductCategory|null $productCategory
 * @property-read \App\Models\Warehouse $warehouse
 *
 * @method static \Database\Factories\InventoryStocktakeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktake newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktake newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktake query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktake whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktake whereCompletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktake whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktake whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktake whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktake whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktake whereProductCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktake whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktake whereStocktakeDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktake whereStocktakeNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktake whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryStocktake whereWarehouseId($value)
 *
 * @mixin \Eloquent
 */
class InventoryStocktake extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryStocktakeFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'stocktake_number',
        'warehouse_id',
        'stocktake_date',
        'status',
        'product_category_id',
        'notes',
        'created_by',
        'completed_by',
        'completed_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'stocktake_date' => 'date',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryStocktakeItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
