<?php

namespace App\Models;

use App\Models\Concerns\BuildsAttributeCasts;
use App\Models\Concerns\HasSupplierRelation;
use Database\Factories\SupplierBillFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $bill_number
 * @property int $supplier_id
 * @property int $branch_id
 * @property int $fiscal_year_id
 * @property int|null $purchase_order_id
 * @property int|null $goods_receipt_id
 * @property string|null $supplier_invoice_number
 * @property Carbon|null $supplier_invoice_date
 * @property Carbon $bill_date
 * @property Carbon $due_date
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
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Branch $branch
 * @property-read User|null $confirmer
 * @property-read User|null $creator
 * @property-read FiscalYear $fiscalYear
 * @property-read GoodsReceipt|null $goodsReceipt
 * @property-read Collection<int, SupplierBillItem> $items
 * @property-read int|null $items_count
 * @property-read JournalEntry|null $journalEntry
 * @property-read Collection<int, ApPaymentAllocation> $paymentAllocations
 * @property-read int|null $payment_allocations_count
 * @property-read Collection<int, ApPayment> $payments
 * @property-read int|null $payments_count
 * @property-read PurchaseOrder|null $purchaseOrder
 * @property-read Supplier $supplier
 *
 * @method static \Database\Factories\SupplierBillFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill whereAmountDue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill whereAmountPaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill whereBillDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill whereBillNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill whereConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill whereConfirmedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill whereDiscountAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill whereFiscalYearId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill whereGoodsReceiptId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill whereGrandTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill whereJournalEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill wherePaymentTerms($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill wherePurchaseOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill whereSupplierInvoiceDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill whereSupplierInvoiceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill whereTaxAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierBill whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class SupplierBill extends Model
{
    /** @use HasFactory<SupplierBillFactory> */
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

    public function updatePaymentStatus(): void
    {
        if (in_array($this->status, ['cancelled', 'void', 'draft'], true)) {
            return;
        }

        $paid = (float) $this->amount_paid;
        $total = (float) $this->grand_total;

        if ($total > 0 && $paid >= $total) {
            $this->update(['status' => 'paid']);
        } elseif ($paid > 0 && $paid < $total) {
            $this->update(['status' => 'partially_paid']);
        } elseif ($paid <= 0 && $this->status === 'partially_paid') {
            $this->update(['status' => 'confirmed']);
        }
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
