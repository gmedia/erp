<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

