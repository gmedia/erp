<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $fiscal_year_id
 * @property int|null $period_month
 * @property int $period_year
 * @property string $closing_type
 * @property string $status
 * @property int|null $closing_journal_entry_id
 * @property int|null $retained_earnings_account_id
 * @property numeric $net_income
 * @property string|null $notes
 * @property int|null $closed_by
 * @property \Illuminate\Support\Carbon|null $closed_at
 * @property int|null $reopened_by
 * @property \Illuminate\Support\Carbon|null $reopened_at
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $closedBy
 * @property-read \App\Models\JournalEntry|null $closingJournalEntry
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\FiscalYear $fiscalYear
 * @property-read \App\Models\User|null $reopenedBy
 * @property-read \App\Models\Account|null $retainedEarningsAccount
 * @method static \Database\Factories\PeriodClosingFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PeriodClosing newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PeriodClosing newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PeriodClosing query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PeriodClosing whereClosedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PeriodClosing whereClosedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PeriodClosing whereClosingJournalEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PeriodClosing whereClosingType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PeriodClosing whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PeriodClosing whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PeriodClosing whereFiscalYearId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PeriodClosing whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PeriodClosing whereNetIncome($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PeriodClosing whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PeriodClosing wherePeriodMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PeriodClosing wherePeriodYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PeriodClosing whereReopenedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PeriodClosing whereReopenedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PeriodClosing whereRetainedEarningsAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PeriodClosing whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PeriodClosing whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PeriodClosing extends Model
{
    /** @use HasFactory<\Database\Factories\PeriodClosingFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'fiscal_year_id',
        'period_month',
        'period_year',
        'closing_type',
        'status',
        'closing_journal_entry_id',
        'retained_earnings_account_id',
        'net_income',
        'notes',
        'closed_by',
        'closed_at',
        'reopened_by',
        'reopened_at',
        'created_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'period_month' => 'integer',
        'period_year' => 'integer',
        'net_income' => 'decimal:2',
        'closed_at' => 'datetime',
        'reopened_at' => 'datetime',
    ];

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function closingJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'closing_journal_entry_id');
    }

    public function retainedEarningsAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'retained_earnings_account_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if period is closed
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Check if this is an annual closing
     */
    public function isAnnual(): bool
    {
        return $this->closing_type === 'annual';
    }
}
