<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $journal_entry_id
 * @property int $account_id
 * @property numeric $debit
 * @property numeric $credit
 * @property string|null $memo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account $account
 * @property-read float $amount
 * @property-read float $net_amount
 * @property-read string $type
 * @property-read \App\Models\JournalEntry $journalEntry
 *
 * @method static \Database\Factories\JournalEntryLineFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine whereCredit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine whereDebit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine whereJournalEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine whereMemo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
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
