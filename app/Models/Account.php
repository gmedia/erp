<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $coa_version_id
 * @property int|null $parent_id
 * @property string $code
 * @property string $name
 * @property string $type
 * @property string|null $sub_type
 * @property string $normal_balance
 * @property int $level
 * @property bool $is_active
 * @property bool $is_cash_flow
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Account> $children
 * @property-read int|null $children_count
 * @property-read \App\Models\CoaVersion $coaVersion
 * @property-read float $balance
 * @property-read float $total_credit
 * @property-read float $total_debit
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\JournalEntryLine> $journalLines
 * @property-read int|null $journal_lines_count
 * @property-read Account|null $parent
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account active()
 * @method static \Database\Factories\AccountFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account ofType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereCoaVersionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereIsCashFlow($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereNormalBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereSubType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Account extends Model
{
    /** @use HasFactory<\Database\Factories\AccountFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'coa_version_id',
        'parent_id',
        'code',
        'name',
        'type',
        'sub_type',
        'normal_balance',
        'level',
        'is_active',
        'is_cash_flow',
        'description',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_cash_flow' => 'boolean',
        'level' => 'integer',
    ];

    public function coaVersion(): BelongsTo
    {
        return $this->belongsTo(CoaVersion::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * Calculate balance based on normal_balance
     * For Debit accounts: Balance = Total Debit - Total Credit
     * For Credit accounts: Balance = Total Credit - Total Debit
     */
    public function getBalanceAttribute(): float
    {
        $lines = $this->journalLines()
            ->whereHas('journalEntry', fn ($q) => $q->where('status', 'posted'))
            ->get();

        $totalDebit = $lines->sum('debit');
        $totalCredit = $lines->sum('credit');

        if ($this->normal_balance === 'debit') {
            return (float) ($totalDebit - $totalCredit);
        }

        return (float) ($totalCredit - $totalDebit);
    }

    /**
     * Calculate balance for a specific date range
     */
    public function balanceForPeriod(Carbon $startDate, Carbon $endDate): float
    {
        $lines = $this->journalLines()
            ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'posted')
                    ->whereBetween('entry_date', [$startDate, $endDate]);
            })
            ->get();

        $totalDebit = $lines->sum('debit');
        $totalCredit = $lines->sum('credit');

        if ($this->normal_balance === 'debit') {
            return (float) ($totalDebit - $totalCredit);
        }

        return (float) ($totalCredit - $totalDebit);
    }

    /**
     * Get total debit for this account
     */
    public function getTotalDebitAttribute(): float
    {
        return (float) $this->journalLines()
            ->whereHas('journalEntry', fn ($q) => $q->where('status', 'posted'))
            ->sum('debit');
    }

    /**
     * Get total credit for this account
     */
    public function getTotalCreditAttribute(): float
    {
        return (float) $this->journalLines()
            ->whereHas('journalEntry', fn ($q) => $q->where('status', 'posted'))
            ->sum('credit');
    }

    /**
     * Scope to get only active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get accounts by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
