<?php

namespace App\Models;

use App\Models\Concerns\BuildsAttributeCasts;
use App\Models\Concerns\HasCustomerRelation;
use Database\Factories\CustomerInvoiceFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $invoice_number
 * @property int $customer_id
 * @property int $branch_id
 * @property int $fiscal_year_id
 * @property Carbon $invoice_date
 * @property Carbon $due_date
 * @property string|null $payment_terms
 * @property string $currency
 * @property numeric $subtotal
 * @property numeric $tax_amount
 * @property numeric $discount_amount
 * @property numeric $grand_total
 * @property numeric $amount_received
 * @property numeric $credit_note_amount
 * @property numeric $amount_due
 * @property string $status
 * @property string|null $notes
 * @property int|null $journal_entry_id
 * @property int|null $created_by
 * @property int|null $sent_by
 * @property Carbon|null $sent_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Branch $branch
 * @property-read User|null $creator
 * @property-read Collection<int, CreditNote> $creditNotes
 * @property-read int|null $credit_notes_count
 * @property-read Customer $customer
 * @property-read FiscalYear $fiscalYear
 * @property-read Collection<int, CustomerInvoiceItem> $items
 * @property-read int|null $items_count
 * @property-read JournalEntry|null $journalEntry
 * @property-read Collection<int, ArReceiptAllocation> $receiptAllocations
 * @property-read int|null $receipt_allocations_count
 * @property-read Collection<int, ArReceipt> $receipts
 * @property-read int|null $receipts_count
 * @property-read User|null $sender
 *
 * @method static \Database\Factories\CustomerInvoiceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInvoice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInvoice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInvoice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInvoice whereAmountDue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInvoice whereAmountReceived($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInvoice whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInvoice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInvoice whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInvoice whereCreditNoteAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInvoice whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInvoice whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInvoice whereDiscountAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInvoice whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInvoice whereFiscalYearId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInvoice whereGrandTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInvoice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInvoice whereInvoiceDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInvoice whereInvoiceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInvoice whereJournalEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInvoice whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInvoice wherePaymentTerms($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInvoice whereSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInvoice whereSentBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInvoice whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInvoice whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInvoice whereTaxAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInvoice whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class CustomerInvoice extends Model
{
    /** @use HasFactory<CustomerInvoiceFactory> */
    use BuildsAttributeCasts, HasCustomerRelation, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'invoice_number',
        'customer_id',
        'branch_id',
        'fiscal_year_id',
        'invoice_date',
        'due_date',
        'payment_terms',
        'currency',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'grand_total',
        'amount_received',
        'credit_note_amount',
        'amount_due',
        'status',
        'notes',
        'journal_entry_id',
        'created_by',
        'sent_by',
        'sent_at',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerInvoiceItem::class);
    }

    public function receiptAllocations(): HasMany
    {
        return $this->hasMany(ArReceiptAllocation::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    public function receipts(): HasManyThrough
    {
        return $this->hasManyThrough(
            ArReceipt::class,
            ArReceiptAllocation::class,
            'customer_invoice_id',
            'id',
            'id',
            'ar_receipt_id'
        );
    }

    public function updatePaymentStatus(): void
    {
        if (in_array($this->status, ['cancelled', 'void', 'draft'], true)) {
            return;
        }

        $received = (float) $this->amount_received;
        $creditNotes = (float) $this->credit_note_amount;
        $total = (float) $this->grand_total;
        $totalSettled = $received + $creditNotes;

        if ($total > 0 && $totalSettled >= $total) {
            $this->update(['status' => 'paid']);
        } elseif ($totalSettled > 0 && $totalSettled < $total) {
            $this->update(['status' => 'partially_paid']);
        } elseif ($totalSettled <= 0 && $this->status === 'partially_paid') {
            $this->update(['status' => 'sent']);
        }
    }

    protected function casts(): array
    {
        return [
            ...$this->dateCasts([
                'invoice_date',
                'due_date',
                'sent_at',
            ]),
            ...$this->decimalCasts([
                'subtotal',
                'tax_amount',
                'discount_amount',
                'grand_total',
                'amount_received',
                'credit_note_amount',
                'amount_due',
            ]),
        ];
    }
}
