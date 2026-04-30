<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $subscription_number Unique subscription reference
 * @property int $customer_id
 * @property int $subscription_plan_id
 * @property int $product_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $trial_start_date
 * @property \Illuminate\Support\Carbon|null $trial_end_date
 * @property \Illuminate\Support\Carbon $start_date Subscription start date (after trial)
 * @property \Illuminate\Support\Carbon $current_period_start Current billing period start
 * @property \Illuminate\Support\Carbon $current_period_end Current billing period end
 * @property \Illuminate\Support\Carbon|null $cancellation_date
 * @property \Illuminate\Support\Carbon|null $cancellation_effective_date When cancellation takes effect
 * @property int $billing_cycles_completed
 * @property bool $auto_renew
 * @property numeric $recurring_amount Amount charged per cycle
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SubscriptionBillingRecord> $billingRecords
 * @property-read int|null $billing_records_count
 * @property-read \App\Models\Customer $customer
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\SubscriptionPlan $subscriptionPlan
 *
 * @method static Builder<static>|CustomerSubscription active()
 * @method static Builder<static>|CustomerSubscription cancelled()
 * @method static \Database\Factories\CustomerSubscriptionFactory factory($count = null, $state = [])
 * @method static Builder<static>|CustomerSubscription newModelQuery()
 * @method static Builder<static>|CustomerSubscription newQuery()
 * @method static Builder<static>|CustomerSubscription onlyTrashed()
 * @method static Builder<static>|CustomerSubscription pastDue()
 * @method static Builder<static>|CustomerSubscription query()
 * @method static Builder<static>|CustomerSubscription trial()
 * @method static Builder<static>|CustomerSubscription whereAutoRenew($value)
 * @method static Builder<static>|CustomerSubscription whereBillingCyclesCompleted($value)
 * @method static Builder<static>|CustomerSubscription whereCancellationDate($value)
 * @method static Builder<static>|CustomerSubscription whereCancellationEffectiveDate($value)
 * @method static Builder<static>|CustomerSubscription whereCreatedAt($value)
 * @method static Builder<static>|CustomerSubscription whereCurrentPeriodEnd($value)
 * @method static Builder<static>|CustomerSubscription whereCurrentPeriodStart($value)
 * @method static Builder<static>|CustomerSubscription whereCustomerId($value)
 * @method static Builder<static>|CustomerSubscription whereDeletedAt($value)
 * @method static Builder<static>|CustomerSubscription whereId($value)
 * @method static Builder<static>|CustomerSubscription whereNotes($value)
 * @method static Builder<static>|CustomerSubscription whereProductId($value)
 * @method static Builder<static>|CustomerSubscription whereRecurringAmount($value)
 * @method static Builder<static>|CustomerSubscription whereStartDate($value)
 * @method static Builder<static>|CustomerSubscription whereStatus($value)
 * @method static Builder<static>|CustomerSubscription whereSubscriptionNumber($value)
 * @method static Builder<static>|CustomerSubscription whereSubscriptionPlanId($value)
 * @method static Builder<static>|CustomerSubscription whereTrialEndDate($value)
 * @method static Builder<static>|CustomerSubscription whereTrialStartDate($value)
 * @method static Builder<static>|CustomerSubscription whereUpdatedAt($value)
 * @method static Builder<static>|CustomerSubscription withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|CustomerSubscription withoutTrashed()
 *
 * @mixin \Eloquent
 */
class CustomerSubscription extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerSubscriptionFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'subscription_number',
        'customer_id',
        'subscription_plan_id',
        'product_id',
        'status',
        'trial_start_date',
        'trial_end_date',
        'start_date',
        'current_period_start',
        'current_period_end',
        'cancellation_date',
        'cancellation_effective_date',
        'billing_cycles_completed',
        'auto_renew',
        'recurring_amount',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'recurring_amount' => 'decimal:2',
        'auto_renew' => 'boolean',
        'trial_start_date' => 'date',
        'trial_end_date' => 'date',
        'start_date' => 'date',
        'current_period_start' => 'date',
        'current_period_end' => 'date',
        'cancellation_date' => 'date',
        'cancellation_effective_date' => 'date',
    ];

    /**
     * Get the customer that owns this subscription.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the subscription plan.
     */
    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the billing records for this subscription.
     */
    public function billingRecords(): HasMany
    {
        return $this->hasMany(SubscriptionBillingRecord::class);
    }

    /**
     * Scope a query to only include active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include trial subscriptions.
     */
    public function scopeTrial($query)
    {
        return $query->where('status', 'trial');
    }

    /**
     * Scope a query to only include past due subscriptions.
     */
    public function scopePastDue($query)
    {
        return $query->where('status', 'past_due');
    }

    /**
     * Scope a query to only include cancelled subscriptions.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Check if the subscription is in trial period.
     */
    public function isInTrial(): bool
    {
        return $this->status === 'trial' &&
               $this->trial_end_date &&
               now()->lte($this->trial_end_date);
    }

    /**
     * Check if the subscription is active or in trial.
     */
    public function isActiveOrTrial(): bool
    {
        return in_array($this->status, ['active', 'trial']);
    }
}
