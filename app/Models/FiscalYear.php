<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\CoaVersion|null $activeCoaVersion
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CoaVersion> $coaVersions
 * @property-read int|null $coa_versions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\JournalEntry> $journalEntries
 * @property-read int|null $journal_entries_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalYear current()
 * @method static \Database\Factories\FiscalYearFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalYear newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalYear newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalYear open()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalYear query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalYear whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalYear whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalYear whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalYear whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalYear whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalYear whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalYear whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class FiscalYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function coaVersions(): HasMany
    {
        return $this->hasMany(CoaVersion::class);
    }

    public function activeCoaVersion(): HasOne
    {
        return $this->hasOne(CoaVersion::class)->where('status', 'active');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    /**
     * Check if fiscal year is open for transactions
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Scope to get open fiscal years
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope to get current fiscal year based on date
     */
    public function scopeCurrent($query)
    {
        return $query->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }
}
