<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $customer_subscription_id
 * @property \Illuminate\Support\Carbon $billing_period_start
 * @property \Illuminate\Support\Carbon $billing_period_end
 * @property numeric $amount
 * @property numeric $tax_amount
 * @property numeric $discount_amount
 * @property numeric $total
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\CustomerSubscription $customerSubscription
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord overdue()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord paid()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionBillingRecord query()
 *
 * @mixin \Eloquent
 */
class SubscriptionBillingRecord extends Model
{
    /** @use HasFactory<\Database\Factories\SubscriptionBillingRecordFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'customer_subscription_id',
        'billing_period_start',
        'billing_period_end',
        'amount',
        'tax_amount',
        'discount_amount',
        'total',
        'status',
        'paid_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
        'paid_at' => 'datetime',
    ];

    public function customerSubscription(): BelongsTo
    {
        return $this->belongsTo(CustomerSubscription::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }
}
