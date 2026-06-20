<?php

namespace App\Models;

use Database\Factories\JournalEntryLineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $journal_entry_id
 * @property int $account_id
 * @property int|null $branch_id
 * @property numeric $debit
 * @property numeric $credit
 * @property string|null $memo
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Account $account
 * @property-read Branch|null $branch
 * @property-read float $amount
 * @property-read float $net_amount
 * @property-read string $type
 * @property-read JournalEntry $journalEntry
 *
 * @method static \Database\Factories\JournalEntryLineFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine whereBranchId($value)
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
    /** @use HasFactory<JournalEntryLineFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'branch_id',
        'debit',
        'credit',
        'memo',
    ];

    /**
     * @var array<string, string>
     */
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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
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
