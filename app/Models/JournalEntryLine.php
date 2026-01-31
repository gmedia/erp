<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntryLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'debit',
        'credit',
        'memo',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the net amount (debit - credit)
     */
    public function getNetAmountAttribute(): float
    {
        return (float) $this->debit - (float) $this->credit;
    }

    /**
     * Get the type based on which column has value
     */
    public function getTypeAttribute(): string
    {
        return $this->debit > 0 ? 'debit' : 'credit';
    }

    /**
     * Get the amount (whichever is non-zero)
     */
    public function getAmountAttribute(): float
    {
        return $this->debit > 0 ? (float) $this->debit : (float) $this->credit;
    }
}
