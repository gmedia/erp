<?php

namespace App\Models;

use Database\Factories\BudgetLineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $budget_id
 * @property int $account_id
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property numeric $allocated_amount
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Budget $budget
 * @property-read Account $account
 *
 * @method static \Database\Factories\BudgetLineFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BudgetLine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BudgetLine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BudgetLine query()
 *
 * @mixin \Eloquent
 */
class BudgetLine extends Model
{
    /** @use HasFactory<BudgetLineFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'budget_id',
        'account_id',
        'period_start',
        'period_end',
        'allocated_amount',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'allocated_amount' => 'decimal:2',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
