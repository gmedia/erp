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
 * @property \Illuminate\Support\Carbon $billing_date
 * @property \Illuminate\Support\Carbon $due_date
 * @property string $subtotal
 * @property string $tax_amount
 * @property string $discount_amount
 * @property string $total_amount
 * @property string $amount_paid
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $paid_date
 * @property string|null $payment_method
 * @property string|null $payment_reference
 * @property int $retry_count
 * @property \Illuminate\Support\Carbon|null $next_retry_date
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord query()
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
