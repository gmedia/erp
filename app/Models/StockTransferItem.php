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
 * @property numeric $quantity
 * @property numeric $quantity_received
 * @property numeric $unit_cost
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\StockTransfer $stockTransfer
 * @property-read \App\Models\Unit $unit
 *
 * @method static \Database\Factories\StockTransferItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransferItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransferItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransferItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransferItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransferItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransferItem whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransferItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransferItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransferItem whereQuantityReceived($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransferItem whereStockTransferId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransferItem whereUnitCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransferItem whereUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransferItem whereUpdatedAt($value)
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
