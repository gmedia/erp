<?php

namespace App\Models;

use Database\Factories\BankReconciliationItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $bank_reconciliation_id
 * @property int|null $journal_entry_line_id
 * @property int|null $account_id
 * @property Carbon $transaction_date
 * @property string $description
 * @property numeric $debit
 * @property numeric $credit
 * @property string $type
 * @property bool $is_reconciled
 * @property string|null $reference
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Account|null $account
 * @property-read BankReconciliation $bankReconciliation
 * @property-read JournalEntryLine|null $journalEntryLine
 *
 * @method static \Database\Factories\BankReconciliationItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliationItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliationItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliationItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliationItem whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliationItem whereBankReconciliationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliationItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliationItem whereCredit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliationItem whereDebit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliationItem whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliationItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliationItem whereIsReconciled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliationItem whereJournalEntryLineId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliationItem whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliationItem whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliationItem whereTransactionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliationItem whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliationItem whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class BankReconciliationItem extends Model
{
    /** @use HasFactory<BankReconciliationItemFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'bank_reconciliation_id',
        'journal_entry_line_id',
        'transaction_date',
        'description',
        'debit',
        'credit',
        'type',
        'is_reconciled',
        'reference',
        'notes',
        'account_id',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'transaction_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'is_reconciled' => 'boolean',
    ];

    public function bankReconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class);
    }

    public function journalEntryLine(): BelongsTo
    {
        return $this->belongsTo(JournalEntryLine::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
