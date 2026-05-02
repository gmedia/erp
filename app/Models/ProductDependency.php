<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property int $related_product_id
 * @property string $type
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\Product $relatedProduct
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDependency addOns()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDependency alternatives()
 * @method static \Database\Factories\ProductDependencyFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDependency newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDependency newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDependency prerequisites()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDependency query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDependency recommended()
 *
 * @mixin \Eloquent
 */
class ProductDependency extends Model
{
    /** @use HasFactory<\Database\Factories\ProductDependencyFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'related_product_id',
        'type',
        'notes',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function relatedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'related_product_id');
    }

    public function scopePrerequisites($query)
    {
        return $query->where('type', 'prerequisite');
    }

    public function scopeRecommended($query)
    {
        return $query->where('type', 'recommended');
    }

    public function scopeAddOns($query)
    {
        return $query->where('type', 'add_on');
    }

    public function scopeAlternatives($query)
    {
        return $query->where('type', 'alternative');
    }
}
