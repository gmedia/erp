<?php

namespace App\Models;

use App\Models\Concerns\BuildsAttributeCasts;
use App\Models\Concerns\HasCustomerRelation;
use Database\Factories\CreditNoteFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $credit_note_number
 * @property int $customer_id
 * @property int|null $customer_invoice_id
 * @property int $branch_id
 * @property int $fiscal_year_id
 * @property Carbon $credit_note_date
 * @property string $reason
 * @property numeric $subtotal
 * @property numeric $tax_amount
 * @property numeric $grand_total
 * @property string $status
 * @property string|null $notes
 * @property int|null $journal_entry_id
 * @property int|null $created_by
 * @property int|null $confirmed_by
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Branch $branch
 * @property-read User|null $confirmer
 * @property-read User|null $creator
 * @property-read Customer $customer
 * @property-read CustomerInvoice|null $customerInvoice
 * @property-read FiscalYear $fiscalYear
 * @property-read Collection<int, CreditNoteItem> $items
 * @property-read int|null $items_count
 * @property-read JournalEntry|null $journalEntry
 *
 * @method static \Database\Factories\CreditNoteFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereConfirmedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereCreditNoteDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereCreditNoteNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereCustomerInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereFiscalYearId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereGrandTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereJournalEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereTaxAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class CreditNote extends Model
{
    /** @use HasFactory<CreditNoteFactory> */
    use BuildsAttributeCasts, HasCustomerRelation, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'credit_note_number',
        'customer_id',
        'customer_invoice_id',
        'branch_id',
        'fiscal_year_id',
        'credit_note_date',
        'reason',
        'subtotal',
        'tax_amount',
        'grand_total',
        'status',
        'notes',
        'journal_entry_id',
        'created_by',
        'confirmed_by',
        'confirmed_at',
    ];

    public function customerInvoice(): BelongsTo
    {
        return $this->belongsTo(CustomerInvoice::class);
    }

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

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class);
    }

    protected function casts(): array
    {
        return [
            ...$this->dateCasts([
                'credit_note_date',
                'confirmed_at',
            ]),
            ...$this->decimalCasts([
                'subtotal',
                'tax_amount',
                'grand_total',
            ]),
        ];
    }
}
