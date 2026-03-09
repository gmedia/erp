<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property int $required_product_id
 * @property string $dependency_type
 * @property int $minimum_quantity Minimum quantity of required product needed
 * @property string|null $description Explanation of the dependency
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\Product $requiredProduct
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDependency active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDependency addOns()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDependency alternatives()
 * @method static \Database\Factories\ProductDependencyFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDependency newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDependency newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDependency prerequisites()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDependency query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDependency recommended()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDependency whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDependency whereDependencyType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDependency whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDependency whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDependency whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDependency whereMinimumQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDependency whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDependency whereRequiredProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDependency whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ProductDependency extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'required_product_id',
        'dependency_type',
        'minimum_quantity',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the product that has the dependency.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the required product.
     */
    public function requiredProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'required_product_id');
    }

    /**
     * Scope a query to only include active dependencies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include prerequisites.
     */
    public function scopePrerequisites($query)
    {
        return $query->where('dependency_type', 'prerequisite');
    }

    /**
     * Scope a query to only include recommended products.
     */
    public function scopeRecommended($query)
    {
        return $query->where('dependency_type', 'recommended');
    }

    /**
     * Scope a query to only include add-ons.
     */
    public function scopeAddOns($query)
    {
        return $query->where('dependency_type', 'add_on');
    }

    /**
     * Scope a query to only include alternatives.
     */
    public function scopeAlternatives($query)
    {
        return $query->where('dependency_type', 'alternative');
    }
}
