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
 * @property \Illuminate\Support\Carbon $transfer_date
 * @property \Illuminate\Support\Carbon|null $expected_arrival_date
 * @property string $status
 * @property string|null $notes
 * @property int|null $requested_by
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property int|null $shipped_by
 * @property \Illuminate\Support\Carbon|null $shipped_at
 * @property int|null $received_by
 * @property \Illuminate\Support\Carbon|null $received_at
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $approvedBy
 * @property-read \App\Models\User|null $createdBy
 * @property-read \App\Models\Warehouse $fromWarehouse
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StockTransferItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\User|null $receivedBy
 * @property-read \App\Models\Employee|null $requestedBy
 * @property-read \App\Models\User|null $shippedBy
 * @property-read \App\Models\Warehouse $toWarehouse
 *
 * @method static \Database\Factories\StockTransferFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereExpectedArrivalDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereFromWarehouseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereReceivedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereReceivedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereRequestedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereShippedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereShippedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereToWarehouseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereTransferDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereTransferNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereUpdatedAt($value)
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
