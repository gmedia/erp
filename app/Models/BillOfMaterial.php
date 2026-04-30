<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $finished_product_id
 * @property int $raw_material_id
 * @property numeric $quantity_required Quantity of raw material needed per 1 unit of finished product
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereFinishedProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereQuantityRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereRawMaterialId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class BillOfMaterial extends Model
{
    /** @use HasFactory<\Database\Factories\BillOfMaterialFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'finished_product_id',
        'raw_material_id',
        'quantity_required',
        'unit_id',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity_required' => 'decimal:4',
    ];

    /**
     * Get the finished product.
     */
    public function finishedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'finished_product_id');
    }

    /**
     * Get the raw material.
     */
    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'raw_material_id');
    }

    /**
     * Get the unit of measurement.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
