<?php

namespace App\Models;

use Database\Factories\GoodsReceiptFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $gr_number
 * @property int $purchase_order_id
 * @property int $warehouse_id
 * @property Carbon $receipt_date
 * @property string|null $supplier_delivery_note
 * @property string $status
 * @property string|null $notes
 * @property int|null $journal_entry_id
 * @property int|null $received_by
 * @property int|null $confirmed_by
 * @property Carbon|null $confirmed_at
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $confirmer
 * @property-read User|null $creator
 * @property-read Collection<int, GoodsReceiptItem> $items
 * @property-read int|null $items_count
 * @property-read JournalEntry|null $journalEntry
 * @property-read PurchaseOrder $purchaseOrder
 * @property-read Employee|null $receiver
 * @property-read Warehouse $warehouse
 *
 * @method static \Database\Factories\GoodsReceiptFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereConfirmedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereGrNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereJournalEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt wherePurchaseOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereReceiptDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereReceivedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereSupplierDeliveryNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereWarehouseId($value)
 *
 * @mixin \Eloquent
 */
class GoodsReceipt extends Model
{
    /** @use HasFactory<GoodsReceiptFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'gr_number',
        'purchase_order_id',
        'warehouse_id',
        'receipt_date',
        'supplier_delivery_note',
        'status',
        'notes',
        'journal_entry_id',
        'received_by',
        'confirmed_by',
        'confirmed_at',
        'created_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'receipt_date' => 'date',
        'confirmed_at' => 'datetime',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'received_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }
}
