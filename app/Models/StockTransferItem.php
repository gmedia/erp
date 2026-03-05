<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $stock_transfer_id
 * @property int $product_id
 * @property int $unit_id
 * @property string $quantity
 * @property string $quantity_received
 * @property string $unit_cost
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read StockTransfer $stockTransfer
 * @property-read Product $product
 * @property-read Unit $unit
 *
 * @method static \Database\Factories\StockTransferItemFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class StockTransferItem extends Model
{
    /** @use HasFactory<\Database\Factories\StockTransferItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'stock_transfer_id',
        'product_id',
        'unit_id',
        'quantity',
        'quantity_received',
        'unit_cost',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'decimal:2',
        'quantity_received' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class);
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
