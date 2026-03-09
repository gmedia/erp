<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string|null $adjustment_number
 * @property int $warehouse_id
 * @property \Illuminate\Support\Carbon $adjustment_date
 * @property string $adjustment_type
 * @property string $status
 * @property int|null $inventory_stocktake_id
 * @property string|null $notes
 * @property int|null $journal_entry_id
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $approvedBy
 * @property-read \App\Models\User|null $createdBy
 * @property-read \App\Models\InventoryStocktake|null $inventoryStocktake
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StockAdjustmentItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\JournalEntry|null $journalEntry
 * @property-read \App\Models\Warehouse $warehouse
 *
 * @method static \Database\Factories\StockAdjustmentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustment whereAdjustmentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustment whereAdjustmentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustment whereAdjustmentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustment whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustment whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustment whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustment whereInventoryStocktakeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustment whereJournalEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustment whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockAdjustment whereWarehouseId($value)
 *
 * @mixin \Eloquent
 */
class StockAdjustment extends Model
{
    /** @use HasFactory<\Database\Factories\StockAdjustmentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'adjustment_number',
        'warehouse_id',
        'adjustment_date',
        'adjustment_type',
        'status',
        'inventory_stocktake_id',
        'notes',
        'journal_entry_id',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'adjustment_date' => 'date',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function inventoryStocktake(): BelongsTo
    {
        return $this->belongsTo(InventoryStocktake::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
