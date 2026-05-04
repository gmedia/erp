<?php

namespace App\Models;

use App\Models\Concerns\BuildsAttributeCasts;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $supplier_bill_id
 * @property int|null $product_id
 * @property int $account_id
 * @property string $description
 * @property numeric $quantity
 * @property numeric $unit_price
 * @property numeric $discount_percent
 * @property numeric $tax_percent
 * @property numeric $line_total
 * @property int|null $goods_receipt_item_id
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SupplierBill $supplierBill
 * @property-read \App\Models\Product|null $product
 * @property-read \App\Models\Account $account
 * @property-read \App\Models\GoodsReceiptItem|null $goodsReceiptItem
 *
 * @mixin \Eloquent
 */
class SupplierBillItem extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierBillItemFactory> */
    use BuildsAttributeCasts, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'supplier_bill_id',
        'product_id',
        'account_id',
        'description',
        'quantity',
        'unit_price',
        'discount_percent',
        'tax_percent',
        'line_total',
        'goods_receipt_item_id',
        'notes',
    ];

    public function supplierBill(): BelongsTo
    {
        return $this->belongsTo(SupplierBill::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function goodsReceiptItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptItem::class);
    }

    protected function casts(): array
    {
        return [
            ...$this->decimalCasts([
                'quantity',
                'unit_price',
                'line_total',
            ]),
            ...$this->decimalCasts([
                'discount_percent',
                'tax_percent',
            ]),
        ];
    }
}
