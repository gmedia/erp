<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property int|null $customer_category_id
 * @property numeric $price
 * @property \Illuminate\Support\Carbon $effective_from
 * @property \Illuminate\Support\Carbon|null $effective_until
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\CustomerCategory|null $customerCategory
 * @property-read \App\Models\Product $product
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice effective()
 * @method static \Database\Factories\ProductPriceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice query()
 *
 * @mixin \Eloquent
 */
class ProductPrice extends Model
{
    /** @use HasFactory<\Database\Factories\ProductPriceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'customer_category_id',
        'price',
        'effective_from',
        'effective_until',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customerCategory(): BelongsTo
    {
        return $this->belongsTo(CustomerCategory::class, 'customer_category_id');
    }

    public function scopeEffective($query)
    {
        return $query->where('effective_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', now());
            });
    }
}
