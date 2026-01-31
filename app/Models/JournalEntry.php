<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'fiscal_year_id',
        'entry_number',
        'entry_date',
        'reference',
        'description',
        'status',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'posted_at' => 'datetime',
    ];

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /**
     * Check if journal entry is balanced (Total Debit = Total Credit)
     */
    public function isBalanced(): bool
    {
        $totalDebit = $this->lines->sum('debit');
        $totalCredit = $this->lines->sum('credit');

        return bccomp((string) $totalDebit, (string) $totalCredit, 2) === 0;
    }

    /**
     * Get total debit amount
     */
    public function getTotalDebitAttribute(): float
    {
        return (float) $this->lines->sum('debit');
    }

    /**
     * Get total credit amount
     */
    public function getTotalCreditAttribute(): float
    {
        return (float) $this->lines->sum('credit');
    }

    /**
     * Scope for posted entries only
     */
    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    /**
     * Scope for draft entries only
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }
}
