<?php

namespace App\Models;

use App\Models\Concerns\BuildsAttributeCasts;
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
 * @property int $product_category_id
 * @property int $unit_id
 * @property int|null $branch_id
 * @property numeric $cost
 * @property numeric $selling_price
 * @property string $billing_model
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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductDependency> $relatedTo
 * @property-read int|null $related_to_count
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product ofType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product query()
 *
 * @mixin \Eloquent
 */
class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use BuildsAttributeCasts, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'product_category_id',
        'unit_id',
        'branch_id',
        'cost',
        'selling_price',
        'billing_model',
        'status',
        'notes',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    public function billOfMaterials(): HasMany
    {
        return $this->hasMany(BillOfMaterial::class, 'finished_product_id');
    }

    public function usedInBillOfMaterials(): HasMany
    {
        return $this->hasMany(BillOfMaterial::class, 'raw_material_id');
    }

    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(ProductDependency::class, 'product_id');
    }

    public function relatedTo(): HasMany
    {
        return $this->hasMany(ProductDependency::class, 'related_product_id');
    }

    public function subscriptionPlans(): HasMany
    {
        return $this->hasMany(SubscriptionPlan::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    protected function casts(): array
    {
        return $this->decimalCasts([
            'cost',
            'selling_price',
        ]);
    }
}
