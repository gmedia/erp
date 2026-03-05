<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    /** @use HasFactory<\Database\Factories\StockAdjustmentItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'stock_adjustment_id',
        'product_id',
        'unit_id',
        'quantity_before',
        'quantity_adjusted',
        'quantity_after',
        'unit_cost',
        'total_cost',
        'reason',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'quantity_before' => 'decimal:2',
        'quantity_adjusted' => 'decimal:2',
        'quantity_after' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class);
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
