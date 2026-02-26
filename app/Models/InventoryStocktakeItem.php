<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

