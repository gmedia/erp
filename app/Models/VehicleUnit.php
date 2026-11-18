<?php

namespace App\Models;

use Database\Factories\VehicleUnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $asset_id
 * @property string $plate_number
 * @property string $vehicle_type
 * @property int|null $capacity
 * @property bool $is_active
 *
 * @method static \Database\Factories\VehicleUnitFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class VehicleUnit extends Model
{
    /** @use HasFactory<VehicleUnitFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'asset_id',
        'plate_number',
        'vehicle_type',
        'capacity',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(VehicleLoan::class, 'vehicle_unit_id');
    }
}
