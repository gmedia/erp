<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $customer_id
 * @property int $subscription_plan_id
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property \Illuminate\Support\Carbon $next_billing_date
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SubscriptionBillingRecord> $billingRecords
 * @property-read int|null $billing_records_count
 * @property-read \App\Models\Customer $customer
 * @property-read \App\Models\SubscriptionPlan $subscriptionPlan
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerSubscription active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerSubscription cancelled()
 * @method static \Database\Factories\CustomerSubscriptionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerSubscription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerSubscription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerSubscription pastDue()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerSubscription query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerSubscription trial()
 *
 * @mixin \Eloquent
 */
class CustomerSubscription extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerSubscriptionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'subscription_plan_id',
        'start_date',
        'end_date',
        'next_billing_date',
        'status',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_billing_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function billingRecords(): HasMany
    {
        return $this->hasMany(SubscriptionBillingRecord::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeTrial($query)
    {
        return $query->where('status', 'trial');
    }

    public function scopePastDue($query)
    {
        return $query->where('status', 'past_due');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }
}
