<?php

namespace App\Models;

use App\Models\Concerns\BuildsAttributeCasts;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code Product/Service code (SKU)
 * @property string $name
 * @property string|null $description
 * @property string $type
 * @property int $category_id
 * @property int $unit_id
 * @property int|null $branch_id
 * @property numeric $cost Production/purchase cost per unit
 * @property numeric $selling_price Default selling price
 * @property numeric|null $markup_percentage Markup % over cost
 * @property string $billing_model How this product is billed
 * @property bool $is_recurring TRUE if this is a subscription product
 * @property int|null $trial_period_days Free trial period in days (null = no trial)
 * @property bool $allow_one_time_purchase Allow buying without subscription
 * @property bool $is_manufactured TRUE if this product is manufactured (has BOM)
 * @property bool $is_purchasable TRUE if can be purchased from suppliers
 * @property bool $is_sellable TRUE if can be sold to customers
 * @property bool $is_taxable
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BillOfMaterial> $billOfMaterials
 * @property-read int|null $bill_of_materials_count
 * @property-read \App\Models\Branch|null $branch
 * @property-read \App\Models\ProductCategory $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductDependency> $dependencies
 * @property-read int|null $dependencies_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductPrice> $prices
 * @property-read int|null $prices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductionOrder> $productionOrders
 * @property-read int|null $production_orders_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductDependency> $requiredBy
 * @property-read int|null $required_by_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductStock> $stocks
 * @property-read int|null $stocks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SubscriptionPlan> $subscriptionPlans
 * @property-read int|null $subscription_plans_count
 * @property-read \App\Models\Unit $unit
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BillOfMaterial> $usedInBillOfMaterials
 * @property-read int|null $used_in_bill_of_materials_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product active()
 * @method static \Database\Factories\ProductFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product manufactured()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product ofType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product purchasable()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product sellable()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereAllowOneTimePurchase($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereBillingModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereIsManufactured($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereIsPurchasable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereIsRecurring($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereIsSellable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereIsTaxable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereMarkupPercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereSellingPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereTrialPeriodDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use BuildsAttributeCasts, HasFactory;

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

    protected function casts(): array
    {
        return [
            ...$this->decimalCasts([
                'cost',
                'selling_price',
                'markup_percentage',
            ]),
            ...$this->booleanCasts([
                'is_recurring',
                'allow_one_time_purchase',
                'is_manufactured',
                'is_purchasable',
                'is_sellable',
                'is_taxable',
            ]),
        ];
    }
}
