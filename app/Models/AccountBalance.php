<?php

namespace App\Models;

use Database\Factories\AccountBalanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $account_id
 * @property int $fiscal_year_id
 * @property int $period_month
 * @property int $period_year
 * @property numeric $opening_balance
 * @property numeric $debit_total
 * @property numeric $credit_total
 * @property numeric $closing_balance
 * @property numeric $movement
 * @property Carbon|null $last_recalculated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Account $account
 * @property-read FiscalYear $fiscalYear
 *
 * @method static \Database\Factories\AccountBalanceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountBalance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountBalance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountBalance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountBalance whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountBalance whereClosingBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountBalance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountBalance whereCreditTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountBalance whereDebitTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountBalance whereFiscalYearId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountBalance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountBalance whereLastRecalculatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountBalance whereMovement($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountBalance whereOpeningBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountBalance wherePeriodMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountBalance wherePeriodYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountBalance whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class AccountBalance extends Model
{
    /** @use HasFactory<AccountBalanceFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'fiscal_year_id',
        'period_month',
        'period_year',
        'opening_balance',
        'debit_total',
        'credit_total',
        'closing_balance',
        'movement',
        'last_recalculated_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'period_month' => 'integer',
        'period_year' => 'integer',
        'opening_balance' => 'decimal:2',
        'debit_total' => 'decimal:2',
        'credit_total' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'movement' => 'decimal:2',
        'last_recalculated_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }
}
