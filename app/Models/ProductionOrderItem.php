<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $production_order_id
 * @property int $raw_material_id
 * @property string $quantity_used
 * @property string $unit_cost
 * @property string $total_cost
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrderItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrderItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrderItem query()
 *
 * @mixin \Eloquent
 */
class ProductionOrderItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'production_order_id',
        'raw_material_id',
        'quantity_used',
        'unit_cost',
        'total_cost',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity_used' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    /**
     * Get the production order that this item belongs to.
     */
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    /**
     * Get the raw material product.
     */
    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'raw_material_id');
    }
}
