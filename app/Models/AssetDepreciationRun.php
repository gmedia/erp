<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $fiscal_year_id
 * @property \Illuminate\Support\Carbon $period_start
 * @property \Illuminate\Support\Carbon $period_end
 * @property string $status
 * @property int|null $journal_entry_id
 * @property int|null $created_by
 * @property int|null $posted_by
 * @property \Illuminate\Support\Carbon|null $posted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $createdBy
 * @property-read \App\Models\FiscalYear $fiscalYear
 * @property-read \App\Models\JournalEntry|null $journalEntry
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AssetDepreciationLine> $lines
 * @property-read int|null $lines_count
 * @property-read \App\Models\User|null $postedBy
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
    use HasFactory;

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
