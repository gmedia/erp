<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $goods_receipt_id
 * @property int $purchase_order_item_id
 * @property int $product_id
 * @property int $unit_id
 * @property numeric $quantity_received
 * @property numeric $quantity_accepted
 * @property numeric $quantity_rejected
 * @property numeric $unit_price
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\GoodsReceipt $goodsReceipt
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\PurchaseOrderItem $purchaseOrderItem
 * @property-read \App\Models\Unit $unit
 *
 * @method static \Database\Factories\GoodsReceiptItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem whereGoodsReceiptId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem wherePurchaseOrderItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem whereQuantityAccepted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem whereQuantityReceived($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem whereQuantityRejected($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem whereUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class GoodsReceiptItem extends Model
{
    /** @use HasFactory<\Database\Factories\GoodsReceiptItemFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'goods_receipt_id',
        'purchase_order_item_id',
        'product_id',
        'unit_id',
        'quantity_received',
        'quantity_accepted',
        'quantity_rejected',
        'unit_price',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'quantity_received' => 'decimal:2',
        'quantity_accepted' => 'decimal:2',
        'quantity_rejected' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
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
