<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

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
            ->whereHas('journalEntry', fn($q) => $q->where('status', 'posted'))
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
            ->whereHas('journalEntry', fn($q) => $q->where('status', 'posted'))
            ->sum('debit');
    }

    /**
     * Get total credit for this account
     */
    public function getTotalCreditAttribute(): float
    {
        return (float) $this->journalLines()
            ->whereHas('journalEntry', fn($q) => $q->where('status', 'posted'))
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
