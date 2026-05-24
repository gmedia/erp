<?php

namespace App\Models;

use App\Models\Concerns\HasSupplierRelation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string|null $return_number
 * @property int $purchase_order_id
 * @property int|null $goods_receipt_id
 * @property int $supplier_id
 * @property int $warehouse_id
 * @property \Illuminate\Support\Carbon $return_date
 * @property string $reason
 * @property string $status
 * @property string|null $notes
 * @property int|null $confirmed_by
 * @property \Illuminate\Support\Carbon|null $confirmed_at
 * @property int|null $journal_entry_id
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $confirmer
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\GoodsReceipt|null $goodsReceipt
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SupplierReturnItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\JournalEntry|null $journalEntry
 * @property-read \App\Models\PurchaseOrder $purchaseOrder
 * @property-read \App\Models\Supplier $supplier
 * @property-read \App\Models\Warehouse $warehouse
 * @method static \Database\Factories\SupplierReturnFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturn newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturn newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturn query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturn whereConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturn whereConfirmedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturn whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturn whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturn whereGoodsReceiptId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturn whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturn whereJournalEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturn whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturn wherePurchaseOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturn whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturn whereReturnDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturn whereReturnNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturn whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturn whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturn whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierReturn whereWarehouseId($value)
 * @mixin \Eloquent
 */
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
        'journal_entry_id',
        'confirmed_by',
        'confirmed_at',
        'created_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'return_date' => 'date',
        'confirmed_at' => 'datetime',
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

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierReturnItem::class);
    }
}
