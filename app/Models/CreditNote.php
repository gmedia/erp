<?php

namespace App\Models;

use App\Models\Concerns\BuildsAttributeCasts;
use App\Models\Concerns\HasCustomerRelation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string|null $credit_note_number
 * @property int $customer_id
 * @property int|null $customer_invoice_id
 * @property int $branch_id
 * @property int $fiscal_year_id
 * @property \Illuminate\Support\Carbon $credit_note_date
 * @property string $reason
 * @property numeric $subtotal
 * @property numeric $tax_amount
 * @property numeric $grand_total
 * @property string $status
 * @property string|null $notes
 * @property int|null $journal_entry_id
 * @property int|null $created_by
 * @property int|null $confirmed_by
 * @property \Illuminate\Support\Carbon|null $confirmed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customer $customer
 * @property-read \App\Models\CustomerInvoice|null $customerInvoice
 * @property-read \App\Models\Branch $branch
 * @property-read \App\Models\FiscalYear $fiscalYear
 * @property-read \App\Models\JournalEntry|null $journalEntry
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\User|null $confirmer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CreditNoteItem> $items
 * @property-read int|null $items_count
 *
 * @mixin \Eloquent
 */
class CreditNote extends Model
{
    /** @use HasFactory<\Database\Factories\CreditNoteFactory> */
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
