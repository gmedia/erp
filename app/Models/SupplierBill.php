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
 * @property string $bill_number
 * @property int $supplier_id
 * @property int $branch_id
 * @property int $fiscal_year_id
 * @property int|null $purchase_order_id
 * @property int|null $goods_receipt_id
 * @property string|null $supplier_invoice_number
 * @property \Illuminate\Support\Carbon|null $supplier_invoice_date
 * @property \Illuminate\Support\Carbon $bill_date
 * @property \Illuminate\Support\Carbon $due_date
 * @property string|null $payment_terms
 * @property string $currency
 * @property numeric $subtotal
 * @property numeric $tax_amount
 * @property numeric $discount_amount
 * @property numeric $grand_total
 * @property numeric $amount_paid
 * @property numeric $amount_due
 * @property string $status
 * @property string|null $notes
 * @property int|null $journal_entry_id
 * @property int|null $created_by
 * @property int|null $confirmed_by
 * @property \Illuminate\Support\Carbon|null $confirmed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SupplierBillItem> $items
 * @property-read int|null $items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ApPaymentAllocation> $paymentAllocations
 * @property-read int|null $payment_allocations_count
 * @property-read \App\Models\Supplier $supplier
 * @property-read \App\Models\Branch $branch
 * @property-read \App\Models\FiscalYear $fiscalYear
 * @property-read \App\Models\PurchaseOrder|null $purchaseOrder
 * @property-read \App\Models\GoodsReceipt|null $goodsReceipt
 * @property-read \App\Models\JournalEntry|null $journalEntry
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\User|null $confirmer
 *
 * @mixin \Eloquent
 */
class SupplierBill extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierBillFactory> */
    use BuildsAttributeCasts, HasFactory, HasSupplierRelation;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'bill_number',
        'supplier_id',
        'branch_id',
        'fiscal_year_id',
        'purchase_order_id',
        'goods_receipt_id',
        'supplier_invoice_number',
        'supplier_invoice_date',
        'bill_date',
        'due_date',
        'payment_terms',
        'currency',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'grand_total',
        'amount_paid',
        'amount_due',
        'status',
        'notes',
        'journal_entry_id',
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

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
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
        return $this->hasMany(SupplierBillItem::class);
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(ApPaymentAllocation::class);
    }

    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(
            ApPayment::class,
            ApPaymentAllocation::class,
            'supplier_bill_id',
            'id',
            'id',
            'ap_payment_id',
        );
    }

    protected function casts(): array
    {
        return [
            ...$this->dateCasts([
                'supplier_invoice_date',
                'bill_date',
                'due_date',
            ]),
            ...$this->decimalCasts([
                'subtotal',
                'tax_amount',
                'discount_amount',
                'grand_total',
                'amount_paid',
                'amount_due',
            ]),
            ...$this->datetimeCasts([
                'confirmed_at',
            ]),
        ];
    }
}
