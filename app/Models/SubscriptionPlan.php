<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property string $billing_interval
 * @property int $billing_interval_count
 * @property string $price
 * @property string $setup_fee
 * @property int|null $trial_period_days
 * @property int|null $minimum_commitment_cycles
 * @property bool $auto_renew
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan query()
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
