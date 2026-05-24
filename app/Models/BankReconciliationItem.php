<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $bank_reconciliation_id
 * @property int|null $journal_entry_line_id
 * @property \Illuminate\Support\Carbon $transaction_date
 * @property string $description
 * @property numeric $debit
 * @property numeric $credit
 * @property string $type
 * @property bool $is_reconciled
 * @property string|null $reference
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\BankReconciliation $bankReconciliation
 * @property-read \App\Models\JournalEntryLine|null $journalEntryLine
 *
 * @method static \Database\Factories\BankReconciliationItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliationItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliationItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliationItem query()
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
    /** @use HasFactory<\Database\Factories\BankReconciliationItemFactory> */
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
}
