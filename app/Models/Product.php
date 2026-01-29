<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string $type
 * @property int $category_id
 * @property int $unit_id
 * @property int|null $branch_id
 * @property string $cost
 * @property string $selling_price
 * @property string|null $markup_percentage
 * @property string $billing_model
 * @property bool $is_recurring
 * @property int|null $trial_period_days
 * @property bool $allow_one_time_purchase
 * @property bool $is_manufactured
 * @property bool $is_purchasable
 * @property bool $is_sellable
 * @property bool $is_taxable
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product query()
 *
 * @mixin \Eloquent
 */
class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'category_id',
        'unit_id',
        'branch_id',
        'cost',
        'selling_price',
        'markup_percentage',
        'billing_model',
        'is_recurring',
        'trial_period_days',
        'allow_one_time_purchase',
        'is_manufactured',
        'is_purchasable',
        'is_sellable',
        'is_taxable',
        'status',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'cost' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'markup_percentage' => 'decimal:2',
        'is_recurring' => 'boolean',
        'allow_one_time_purchase' => 'boolean',
        'is_manufactured' => 'boolean',
        'is_purchasable' => 'boolean',
        'is_sellable' => 'boolean',
        'is_taxable' => 'boolean',
    ];

    /**
     * Get the category that the product belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * Get the unit that the product uses.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the branch that the product belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the product prices for different customer categories.
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    /**
     * Get the stock records for this product across branches.
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    /**
     * Get the bill of materials entries where this product is the finished product.
     */
    public function billOfMaterials(): HasMany
    {
        return $this->hasMany(BillOfMaterial::class, 'finished_product_id');
    }

    /**
     * Get the bill of materials entries where this product is a raw material.
     */
    public function usedInBillOfMaterials(): HasMany
    {
        return $this->hasMany(BillOfMaterial::class, 'raw_material_id');
    }

    /**
     * Get the production orders for this product.
     */
    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    /**
     * Get the dependencies where this product depends on other products.
     */
    public function dependencies(): HasMany
    {
        return $this->hasMany(ProductDependency::class, 'product_id');
    }

    /**
     * Get the dependencies where this product is required by other products.
     */
    public function requiredBy(): HasMany
    {
        return $this->hasMany(ProductDependency::class, 'required_product_id');
    }

    /**
     * Get the subscription plans for this product.
     */
    public function subscriptionPlans(): HasMany
    {
        return $this->hasMany(SubscriptionPlan::class);
    }

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include products of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include sellable products.
     */
    public function scopeSellable($query)
    {
        return $query->where('is_sellable', true);
    }

    /**
     * Scope a query to only include purchasable products.
     */
    public function scopePurchasable($query)
    {
        return $query->where('is_purchasable', true);
    }

    /**
     * Scope a query to only include manufactured products.
     */
    public function scopeManufactured($query)
    {
        return $query->where('is_manufactured', true);
    }
}
