<?php

namespace App\Models;

use App\Models\Concerns\BuildsAttributeCasts;
use App\Models\Concerns\HasSupplierRelation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int $id
 * @property string $payment_number
 * @property int $supplier_id
 * @property int $branch_id
 * @property int $fiscal_year_id
 * @property \Illuminate\Support\Carbon $payment_date
 * @property string $payment_method
 * @property int $bank_account_id
 * @property string $currency
 * @property numeric $total_amount
 * @property numeric $total_allocated
 * @property numeric $total_unallocated
 * @property string|null $reference
 * @property string $status
 * @property string|null $notes
 * @property int|null $journal_entry_id
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property int|null $created_by
 * @property int|null $confirmed_by
 * @property \Illuminate\Support\Carbon|null $confirmed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ApPaymentAllocation> $allocations
 * @property-read int|null $allocations_count
 * @property-read \App\Models\Supplier $supplier
 * @property-read \App\Models\Branch $branch
 * @property-read \App\Models\FiscalYear $fiscalYear
 * @property-read \App\Models\Account $bankAccount
 * @property-read \App\Models\JournalEntry|null $journalEntry
 * @property-read \App\Models\User|null $approver
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\User|null $confirmer
 *
 * @mixin \Eloquent
 */
class ApPayment extends Model
{
    /** @use HasFactory<\Database\Factories\ApPaymentFactory> */
    use BuildsAttributeCasts, HasFactory, HasSupplierRelation;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'payment_number',
        'supplier_id',
        'branch_id',
        'fiscal_year_id',
        'payment_date',
        'payment_method',
        'bank_account_id',
        'currency',
        'total_amount',
        'total_allocated',
        'total_unallocated',
        'reference',
        'status',
        'notes',
        'journal_entry_id',
        'approved_by',
        'approved_at',
        'created_by',
        'confirmed_by',
        'confirmed_at',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ApPaymentAllocation::class);
    }

    public function bills(): HasManyThrough
    {
        return $this->hasManyThrough(
            SupplierBill::class,
            ApPaymentAllocation::class,
            'ap_payment_id',
            'id',
            'id',
            'supplier_bill_id',
        );
    }

    protected function casts(): array
    {
        return [
            ...$this->dateCasts([
                'payment_date',
            ]),
            ...$this->decimalCasts([
                'total_amount',
                'total_allocated',
                'total_unallocated',
            ]),
            ...$this->datetimeCasts([
                'approved_at',
                'confirmed_at',
            ]),
        ];
    }
}
