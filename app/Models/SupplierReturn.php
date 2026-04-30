<?php

namespace App\Models;

use App\Models\Concerns\HasSupplierRelation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierReturn extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierReturnFactory> */
    use HasFactory, HasSupplierRelation;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'return_number',
        'purchase_order_id',
        'goods_receipt_id',
        'supplier_id',
        'warehouse_id',
        'return_date',
        'reason',
        'status',
        'notes',
        'created_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'return_date' => 'date',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierReturnItem::class);
    }
}
