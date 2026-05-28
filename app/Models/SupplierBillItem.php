<?php

namespace App\Models;

use App\Models\Concerns\BuildsAttributeCasts;
use Database\Factories\SupplierBillItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

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
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Account $account
 * @property-read GoodsReceiptItem|null $goodsReceiptItem
 * @property-read Product|null $product
 * @property-read SupplierBill $supplierBill
 *
 * @method static \Database\Factories\SupplierBillItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBillItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBillItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBillItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBillItem whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBillItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBillItem whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBillItem whereDiscountPercent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBillItem whereGoodsReceiptItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBillItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBillItem whereLineTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBillItem whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBillItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBillItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBillItem whereSupplierBillId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBillItem whereTaxPercent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBillItem whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBillItem whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class SupplierBillItem extends Model
{
    /** @use HasFactory<SupplierBillItemFactory> */
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
