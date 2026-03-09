<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $customer_subscription_id
 * @property string $invoice_number
 * @property \Illuminate\Support\Carbon $period_start
 * @property \Illuminate\Support\Carbon $period_end
 * @property \Illuminate\Support\Carbon $billing_date When invoice was generated
 * @property \Illuminate\Support\Carbon $due_date Payment due date
 * @property numeric $subtotal
 * @property numeric $tax_amount
 * @property numeric $discount_amount
 * @property numeric $total_amount
 * @property numeric $amount_paid
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $paid_date
 * @property string|null $payment_method
 * @property string|null $payment_reference
 * @property int $retry_count Number of payment retry attempts
 * @property \Illuminate\Support\Carbon|null $next_retry_date
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\CustomerSubscription $customerSubscription
 * @property-read string $amount_due
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord draft()
 * @method static \Database\Factories\SubscriptionBillingRecordFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord overdue()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord paid()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord whereAmountPaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord whereBillingDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord whereCustomerSubscriptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord whereDiscountAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord whereInvoiceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord whereNextRetryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord wherePaidDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord wherePaymentReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord wherePeriodEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord wherePeriodStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord whereRetryCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord whereTaxAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class SubscriptionBillingRecord extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_subscription_id',
        'invoice_number',
        'period_start',
        'period_end',
        'billing_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'amount_paid',
        'status',
        'paid_date',
        'payment_method',
        'payment_reference',
        'retry_count',
        'next_retry_date',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'billing_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'next_retry_date' => 'date',
    ];

    /**
     * Get the customer subscription that this billing record belongs to.
     */
    public function customerSubscription(): BelongsTo
    {
        return $this->belongsTo(CustomerSubscription::class);
    }

    /**
     * Get the amount due (total - paid).
     */
    public function getAmountDueAttribute(): string
    {
        return bcsub($this->total_amount, $this->amount_paid, 2);
    }

    /**
     * Check if the invoice is fully paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid' ||
               bccomp($this->amount_paid, $this->total_amount, 2) >= 0;
    }

    /**
     * Check if the invoice is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === 'overdue' ||
               ($this->status === 'pending' && now()->gt($this->due_date));
    }

    /**
     * Scope a query to only include pending invoices.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include paid invoices.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope a query to only include overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->orWhere(function ($q) {
                $q->where('status', 'pending')
                    ->where('due_date', '<', now());
            });
    }

    /**
     * Scope a query to only include draft invoices.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }
}
