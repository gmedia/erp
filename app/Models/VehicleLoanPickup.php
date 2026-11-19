<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\VehicleLoanPickupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $vehicle_loan_id
 * @property int $requester_id
 * @property string $photo_mode
 * @property string $selfie_photo_path
 * @property string $selfie_photo_name
 * @property int|null $driver_snapshot_id
 * @property int|null $unit_snapshot_id
 * @property Carbon $picked_at
 * @property Carbon|null $key_handover_at
 * @property int|null $security_user_id
 * @property-read VehicleLoan $loan
 * @property-read VehicleLoanReturn|null $returnRecord
 *
 * @method static \Database\Factories\VehicleLoanPickupFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class VehicleLoanPickup extends Model
{
    /** @use HasFactory<VehicleLoanPickupFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vehicle_loan_id',
        'requester_id',
        'photo_mode',
        'selfie_photo_path',
        'selfie_photo_name',
        'driver_snapshot_id',
        'unit_snapshot_id',
        'picked_at',
        'key_handover_at',
        'security_user_id',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'picked_at' => 'datetime',
        'key_handover_at' => 'datetime',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(VehicleLoan::class, 'vehicle_loan_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function driverSnapshot(): BelongsTo
    {
        return $this->belongsTo(VehicleDriver::class, 'driver_snapshot_id');
    }

    public function unitSnapshot(): BelongsTo
    {
        return $this->belongsTo(VehicleUnit::class, 'unit_snapshot_id');
    }

    public function security(): BelongsTo
    {
        return $this->belongsTo(User::class, 'security_user_id');
    }

    public function returnRecord(): HasOne
    {
        return $this->hasOne(VehicleLoanReturn::class, 'pickup_id');
    }
}
