<?php

namespace App\Models;

use Database\Factories\VehicleDriverFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $employee_id
 * @property string|null $license_number
 * @property string|null $license_type
 * @property string|null $license_expiry
 * @property bool $is_active
 *
 * @method static \Database\Factories\VehicleDriverFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class VehicleDriver extends Model
{
    /** @use HasFactory<VehicleDriverFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'license_number',
        'license_type',
        'license_expiry',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'license_expiry' => 'date',
        'is_active' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(VehicleLoan::class, 'driver_id');
    }
}
