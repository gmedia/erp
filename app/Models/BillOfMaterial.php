<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $finished_product_id
 * @property int $raw_material_id
 * @property numeric $quantity
 * @property numeric $waste_percentage
 * @property int $unit_id
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Product $finishedProduct
 * @property-read \App\Models\Product $rawMaterial
 * @property-read \App\Models\Unit $unit
 *
 * @method static \Database\Factories\BillOfMaterialFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial query()
 *
 * @mixin \Eloquent
 */
class BillOfMaterial extends Model
{
    /** @use HasFactory<\Database\Factories\BillOfMaterialFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'finished_product_id',
        'raw_material_id',
        'quantity',
        'waste_percentage',
        'unit_id',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'decimal:4',
        'waste_percentage' => 'decimal:2',
    ];

    public function finishedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'finished_product_id');
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'raw_material_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
