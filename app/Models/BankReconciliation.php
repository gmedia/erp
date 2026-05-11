<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $account_id
 * @property int $fiscal_year_id
 * @property \Illuminate\Support\Carbon $reconciliation_date
 * @property \Illuminate\Support\Carbon $period_start
 * @property \Illuminate\Support\Carbon $period_end
 * @property numeric $statement_balance
 * @property numeric $book_balance
 * @property numeric $reconciled_balance
 * @property numeric $difference
 * @property string $status
 * @property string|null $notes
 * @property int|null $completed_by
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account $account
 * @property-read \App\Models\FiscalYear $fiscalYear
 * @property-read \App\Models\User|null $completedBy
 * @property-read \App\Models\User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BankReconciliationItem> $items
 * @property-read int|null $items_count
 *
 * @method static \Database\Factories\BankReconciliationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankReconciliation query()
 *
 * @mixin \Eloquent
 */
class BankReconciliation extends Model
{
    /** @use HasFactory<\Database\Factories\BankReconciliationFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
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
}
