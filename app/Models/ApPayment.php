<?php

namespace App\Models;

use App\Models\Concerns\BuildsAttributeCasts;
use App\Models\Concerns\HasFinancialTransactionRelations;
use App\Models\Concerns\HasSupplierRelation;
use Database\Factories\ApPaymentFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $payment_number
 * @property int $supplier_id
 * @property int $branch_id
 * @property int $fiscal_year_id
 * @property Carbon $payment_date
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
 * @property Carbon|null $approved_at
 * @property int|null $created_by
 * @property int|null $confirmed_by
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, ApPaymentAllocation> $allocations
 * @property-read int|null $allocations_count
 * @property-read User|null $approver
 * @property-read Account $bankAccount
 * @property-read Collection<int, SupplierBill> $bills
 * @property-read int|null $bills_count
 * @property-read Branch $branch
 * @property-read User|null $confirmer
 * @property-read User|null $creator
 * @property-read FiscalYear $fiscalYear
 * @property-read JournalEntry|null $journalEntry
 * @property-read Supplier $supplier
 *
 * @method static \Database\Factories\ApPaymentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPayment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPayment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPayment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPayment whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPayment whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPayment whereBankAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPayment whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPayment whereConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPayment whereConfirmedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPayment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPayment whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPayment whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPayment whereFiscalYearId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPayment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPayment whereJournalEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPayment whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPayment wherePaymentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPayment wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPayment wherePaymentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPayment whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPayment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPayment whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPayment whereTotalAllocated($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPayment whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPayment whereTotalUnallocated($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApPayment whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ApPayment extends Model
{
    /** @use HasFactory<ApPaymentFactory> */
    use BuildsAttributeCasts, HasFactory, HasFinancialTransactionRelations, HasSupplierRelation;

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

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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
