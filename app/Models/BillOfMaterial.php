<?php

namespace App\Models;

use Database\Factories\BillOfMaterialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $finished_product_id
 * @property int $raw_material_id
 * @property numeric $quantity Quantity of raw material needed per 1 unit of finished product
 * @property numeric $waste_percentage
 * @property int $unit_id
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $finishedProduct
 * @property-read Product $rawMaterial
 * @property-read Unit $unit
 *
 * @method static \Database\Factories\BillOfMaterialFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereFinishedProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereRawMaterialId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereWastePercentage($value)
 *
 * @mixin \Eloquent
 */
class BillOfMaterial extends Model
{
    /** @use HasFactory<BillOfMaterialFactory> */
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
