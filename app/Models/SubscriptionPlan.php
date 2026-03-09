<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $product_id
 * @property string $name Plan name: Monthly, Quarterly, Annual
 * @property string $code Unique plan code: PRD-001-MONTHLY
 * @property string|null $description
 * @property string $billing_interval How often customer is billed
 * @property int $billing_interval_count Multiplier for interval (e.g., 3 months = interval:monthly, count:3)
 * @property numeric $price Recurring price per billing cycle
 * @property numeric $setup_fee One-time setup/activation fee
 * @property int|null $trial_period_days Free trial days (overrides product setting)
 * @property int|null $minimum_commitment_cycles Minimum billing cycles required (null = no minimum)
 * @property bool $auto_renew Auto-renew at end of commitment
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CustomerSubscription> $customerSubscriptions
 * @property-read int|null $customer_subscriptions_count
 * @property-read string $total_price
 * @property-read \App\Models\Product $product
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan active()
 * @method static \Database\Factories\SubscriptionPlanFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan whereAutoRenew($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan whereBillingInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan whereBillingIntervalCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan whereMinimumCommitmentCycles($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan whereSetupFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan whereTrialPeriodDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class SubscriptionPlan extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'name',
        'code',
        'description',
        'billing_interval',
        'billing_interval_count',
        'price',
        'setup_fee',
        'trial_period_days',
        'minimum_commitment_cycles',
        'auto_renew',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'auto_renew' => 'boolean',
    ];

    /**
     * Get the product that this plan belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the customer subscriptions for this plan.
     */
    public function customerSubscriptions(): HasMany
    {
        return $this->hasMany(CustomerSubscription::class);
    }

    /**
     * Scope a query to only include active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get the total price including setup fee.
     */
    public function getTotalPriceAttribute(): string
    {
        return bcadd($this->price, $this->setup_fee, 2);
    }
}
