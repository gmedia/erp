<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property int $customer_category_id
 * @property string $price
 * @property \Illuminate\Support\Carbon|null $effective_from
 * @property \Illuminate\Support\Carbon|null $effective_until
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice query()
 *
 * @mixin \Eloquent
 */
class ProductPrice extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    /**
     * Get the product that this price belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the customer category that this price applies to.
     */
    public function customerCategory(): BelongsTo
    {
        return $this->belongsTo(CustomerCategory::class, 'customer_category_id');
    }

    /**
     * Scope a query to only include currently effective prices.
     */
    public function scopeEffective($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('effective_from')
                ->orWhere('effective_from', '<=', now());
        })->where(function ($q) {
            $q->whereNull('effective_until')
                ->orWhere('effective_until', '>=', now());
        });
    }
}
