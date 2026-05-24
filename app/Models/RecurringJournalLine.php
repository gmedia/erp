<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $recurring_journal_id
 * @property int $account_id
 * @property numeric $debit
 * @property numeric $credit
 * @property string|null $memo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account $account
 * @property-read \App\Models\RecurringJournal $recurringJournal
 *
 * @method static \Database\Factories\RecurringJournalLineFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringJournalLine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringJournalLine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringJournalLine query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringJournalLine whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringJournalLine whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringJournalLine whereCredit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringJournalLine whereDebit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringJournalLine whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringJournalLine whereMemo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringJournalLine whereRecurringJournalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringJournalLine whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class RecurringJournalLine extends Model
{
    /** @use HasFactory<\Database\Factories\RecurringJournalLineFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'recurring_journal_id',
        'account_id',
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

    public function recurringJournal(): BelongsTo
    {
        return $this->belongsTo(RecurringJournal::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
