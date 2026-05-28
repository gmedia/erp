<?php

namespace App\Models;

use App\Models\Concerns\BuildsAttributeCasts;
use App\Models\Concerns\HasCustomerRelation;
use App\Models\Concerns\HasFinancialTransactionRelations;
use Database\Factories\ArReceiptFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $receipt_number
 * @property int $customer_id
 * @property int $branch_id
 * @property int $fiscal_year_id
 * @property Carbon $receipt_date
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
 * @property int|null $created_by
 * @property int|null $confirmed_by
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, ArReceiptAllocation> $allocations
 * @property-read int|null $allocations_count
 * @property-read Account $bankAccount
 * @property-read Branch $branch
 * @property-read User|null $confirmer
 * @property-read User|null $creator
 * @property-read Customer $customer
 * @property-read FiscalYear $fiscalYear
 * @property-read Collection<int, CustomerInvoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read JournalEntry|null $journalEntry
 *
 * @method static \Database\Factories\ArReceiptFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceipt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceipt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceipt query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceipt whereBankAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceipt whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceipt whereConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceipt whereConfirmedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceipt whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceipt whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceipt whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceipt whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceipt whereFiscalYearId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceipt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceipt whereJournalEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceipt whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceipt wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceipt whereReceiptDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceipt whereReceiptNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceipt whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceipt whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceipt whereTotalAllocated($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceipt whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceipt whereTotalUnallocated($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArReceipt whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ArReceipt extends Model
{
    /** @use HasFactory<ArReceiptFactory> */
    use BuildsAttributeCasts, HasCustomerRelation, HasFactory, HasFinancialTransactionRelations;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'receipt_number',
        'customer_id',
        'branch_id',
        'fiscal_year_id',
        'receipt_date',
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
        'created_by',
        'confirmed_by',
        'confirmed_at',
    ];

    public function allocations(): HasMany
    {
        return $this->hasMany(ArReceiptAllocation::class);
    }

    public function invoices(): HasManyThrough
    {
        return $this->hasManyThrough(
            CustomerInvoice::class,
            ArReceiptAllocation::class,
            'ar_receipt_id',
            'id',
            'id',
            'customer_invoice_id'
        );
    }

    protected function casts(): array
    {
        return [
            ...$this->dateCasts([
                'receipt_date',
                'confirmed_at',
            ]),
            ...$this->decimalCasts([
                'total_amount',
                'total_allocated',
                'total_unallocated',
            ]),
        ];
    }
}
