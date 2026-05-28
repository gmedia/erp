<?php

namespace App\Models;

use Database\Factories\SupplierReturnItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $supplier_return_id
 * @property int $goods_receipt_item_id
 * @property int $product_id
 * @property int|null $unit_id
 * @property numeric $quantity_returned
 * @property numeric $unit_price
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read GoodsReceiptItem $goodsReceiptItem
 * @property-read Product $product
 * @property-read SupplierReturn $supplierReturn
 * @property-read Unit|null $unit
 *
 * @method static \Database\Factories\SupplierReturnItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturnItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturnItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturnItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturnItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturnItem whereGoodsReceiptItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturnItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturnItem whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturnItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturnItem whereQuantityReturned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturnItem whereSupplierReturnId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturnItem whereUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturnItem whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturnItem whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class SupplierReturnItem extends Model
{
    /** @use HasFactory<SupplierReturnItemFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'supplier_return_id',
        'goods_receipt_item_id',
        'product_id',
        'unit_id',
        'quantity_returned',
        'unit_price',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'quantity_returned' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    public function supplierReturn(): BelongsTo
    {
        return $this->belongsTo(SupplierReturn::class);
    }

    public function goodsReceiptItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptItem::class);
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
