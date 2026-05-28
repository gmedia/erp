<?php

namespace App\Models;

use Database\Factories\AssetDepreciationRunFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $fiscal_year_id
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property string $status
 * @property int|null $journal_entry_id
 * @property int|null $created_by
 * @property int|null $posted_by
 * @property Carbon|null $posted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $createdBy
 * @property-read FiscalYear $fiscalYear
 * @property-read JournalEntry|null $journalEntry
 * @property-read Collection<int, AssetDepreciationLine> $lines
 * @property-read int|null $lines_count
 * @property-read User|null $postedBy
 *
 * @method static \Database\Factories\AssetDepreciationRunFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetDepreciationRun newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetDepreciationRun newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetDepreciationRun query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetDepreciationRun whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetDepreciationRun whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetDepreciationRun whereFiscalYearId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetDepreciationRun whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetDepreciationRun whereJournalEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetDepreciationRun wherePeriodEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetDepreciationRun wherePeriodStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetDepreciationRun wherePostedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetDepreciationRun wherePostedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetDepreciationRun whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetDepreciationRun whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class AssetDepreciationRun extends Model
{
    /** @use HasFactory<AssetDepreciationRunFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'fiscal_year_id',
        'period_start',
        'period_end',
        'status',
        'journal_entry_id',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'posted_at' => 'datetime',
        'fiscal_year_id' => 'integer',
        'journal_entry_id' => 'integer',
        'created_by' => 'integer',
        'posted_by' => 'integer',
    ];

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(AssetDepreciationLine::class);
    }
}
