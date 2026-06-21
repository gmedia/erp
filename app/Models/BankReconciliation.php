<?php

namespace App\Models;

use Database\Factories\BankReconciliationFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $account_id
 * @property int|null $branch_id
 * @property int $fiscal_year_id
 * @property Carbon $reconciliation_date
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property numeric $statement_balance
 * @property numeric $book_balance
 * @property numeric $reconciled_balance
 * @property numeric $difference
 * @property string $status
 * @property string|null $notes
 * @property int|null $journal_entry_id
 * @property int|null $completed_by
 * @property Carbon|null $completed_at
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Account $account
 * @property-read Branch|null $branch
 * @property-read User|null $completedBy
 * @property-read User|null $creator
 * @property-read FiscalYear $fiscalYear
 * @property-read Collection<int, BankReconciliationItem> $items
 * @property-read int|null $items_count
 * @property-read JournalEntry|null $journalEntry
 *
 * @method static \Database\Factories\BankReconciliationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliation whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliation whereBookBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliation whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliation whereCompletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliation whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliation whereDifference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliation whereFiscalYearId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliation whereJournalEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliation whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliation wherePeriodEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliation wherePeriodStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliation whereReconciledBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliation whereReconciliationDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliation whereStatementBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliation whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class BankReconciliation extends Model
{
    /** @use HasFactory<BankReconciliationFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'branch_id',
        'fiscal_year_id',
        'reconciliation_date',
        'period_start',
        'period_end',
        'statement_balance',
        'book_balance',
        'reconciled_balance',
        'difference',
        'status',
        'notes',
        'completed_by',
        'completed_at',
        'created_by',
        'journal_entry_id',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'reconciliation_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'statement_balance' => 'decimal:2',
        'book_balance' => 'decimal:2',
        'reconciled_balance' => 'decimal:2',
        'difference' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BankReconciliationItem::class);
    }

    /**
     * Check if reconciliation is complete (difference = 0)
     */
    public function isReconciled(): bool
    {
        return bccomp((string) $this->difference, '0', 2) === 0;
    }

    /**
     * Recalculate reconciled_balance and difference based on current reconciled items.
     */
    public function recalculateBalances(): void
    {
        $reconciledTotal = $this->items()
            ->where('is_reconciled', true)
            ->selectRaw('COALESCE(SUM(credit), 0) - COALESCE(SUM(debit), 0) as net')
            ->value('net') ?? 0;

        $reconciledBalance = round((float) $this->book_balance + (float) $reconciledTotal, 2);
        $difference = round((float) $this->statement_balance - $reconciledBalance, 2);

        $this->update([
            'reconciled_balance' => $reconciledBalance,
            'difference' => $difference,
        ]);
    }
}
