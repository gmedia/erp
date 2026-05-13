<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int|null $fiscal_year_id
 * @property string $frequency
 * @property \Illuminate\Support\Carbon $next_run_date
 * @property \Illuminate\Support\Carbon|null $last_run_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property numeric $total_amount
 * @property bool $auto_post
 * @property bool $is_active
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\FiscalYear|null $fiscalYear
 * @property-read \App\Models\User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RecurringJournalLine> $lines
 * @property-read int|null $lines_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\JournalEntry> $generatedEntries
 * @property-read int|null $generated_entries_count
 *
 * @method static \Database\Factories\RecurringJournalFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringJournal active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringJournal due()
 *
 * @mixin \Eloquent
 */
class RecurringJournal extends Model
{
    /** @use HasFactory<\Database\Factories\RecurringJournalFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'fiscal_year_id',
        'frequency',
        'next_run_date',
        'last_run_date',
        'end_date',
        'total_amount',
        'auto_post',
        'is_active',
        'created_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'next_run_date' => 'date',
        'last_run_date' => 'date',
        'end_date' => 'date',
        'total_amount' => 'decimal:2',
        'auto_post' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(RecurringJournalLine::class);
    }

    public function generatedEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'source_id')
            ->where('source_type', self::class);
    }

    /**
     * Check if the journal template is balanced (total debit = total credit)
     */
    public function isBalanced(): bool
    {
        $totalDebit = $this->lines->sum('debit');
        $totalCredit = $this->lines->sum('credit');

        return bccomp((string) $totalDebit, (string) $totalCredit, 2) === 0;
    }

    /**
     * Scope to get only active recurring journals
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get recurring journals that are due for execution
     */
    public function scopeDue($query)
    {
        return $query->where('is_active', true)
            ->where('next_run_date', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now()->toDateString());
            });
    }
}
