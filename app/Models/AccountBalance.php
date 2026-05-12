<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
 * @property \Illuminate\Support\Carbon|null $last_recalculated_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account $account
 * @property-read \App\Models\FiscalYear $fiscalYear
 *
 * @method static \Database\Factories\AccountBalanceFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class AccountBalance extends Model
{
    /** @use HasFactory<\Database\Factories\AccountBalanceFactory> */
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
