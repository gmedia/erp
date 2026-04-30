<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    /** @use HasFactory<\Database\Factories\GoodsReceiptFactory> */
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

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }
}
