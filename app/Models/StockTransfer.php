<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string|null $transfer_number
 * @property int $from_warehouse_id
 * @property int $to_warehouse_id
 * @property string $transfer_date
 * @property string|null $expected_arrival_date
 * @property string $status
 * @property string|null $notes
 * @property int|null $requested_by
 * @property int|null $approved_by
 * @property string|null $approved_at
 * @property int|null $shipped_by
 * @property string|null $shipped_at
 * @property int|null $received_by
 * @property string|null $received_at
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StockTransferItem> $items
 * @property-read Warehouse $fromWarehouse
 * @property-read Warehouse $toWarehouse
 * @property-read Employee|null $requestedBy
 * @property-read User|null $approvedBy
 * @property-read User|null $shippedBy
 * @property-read User|null $receivedBy
 * @property-read User|null $createdBy
 *
 * @method static \Database\Factories\StockTransferFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class StockTransfer extends Model
{
    /** @use HasFactory<\Database\Factories\StockTransferFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'transfer_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'transfer_date',
        'expected_arrival_date',
        'status',
        'notes',
        'requested_by',
        'approved_by',
        'approved_at',
        'shipped_by',
        'shipped_at',
        'received_by',
        'received_at',
        'created_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'transfer_date' => 'date',
        'expected_arrival_date' => 'date',
        'approved_at' => 'datetime',
        'shipped_at' => 'datetime',
        'received_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function shippedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shipped_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
