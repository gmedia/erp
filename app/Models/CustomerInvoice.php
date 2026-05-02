<?php

namespace App\Models;

use App\Models\Concerns\BuildsAttributeCasts;
use App\Models\Concerns\HasCustomerRelation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int $id
 * @property string|null $invoice_number
 * @property int $customer_id
 * @property int $branch_id
 * @property int $fiscal_year_id
 * @property \Illuminate\Support\Carbon $invoice_date
 * @property \Illuminate\Support\Carbon $due_date
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
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customer $customer
 * @property-read \App\Models\Branch $branch
 * @property-read \App\Models\FiscalYear $fiscalYear
 * @property-read \App\Models\JournalEntry|null $journalEntry
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\User|null $sender
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CustomerInvoiceItem> $items
 * @property-read int|null $items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ArReceiptAllocation> $receiptAllocations
 * @property-read int|null $receipt_allocations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CreditNote> $creditNotes
 * @property-read int|null $credit_notes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ArReceipt> $receipts
 * @property-read int|null $receipts_count
 *
 * @mixin \Eloquent
 */
class CustomerInvoice extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerInvoiceFactory> */
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
