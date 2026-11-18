<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\VehicleLoanReturnFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $pickup_id
 * @property int $vehicle_loan_id
 * @property int $returner_id
 * @property string $handover_photo_path
 * @property string $condition_photo_path
 * @property bool $incident
 * @property string|null $incident_note
 * @property int|null $odometer_start
 * @property int|null $odometer_end
 * @property Carbon $returned_at
 * @property int|null $received_by_security_id
 *
 * @method static \Database\Factories\VehicleLoanReturnFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class VehicleLoanReturn extends Model
{
    /** @use HasFactory<VehicleLoanReturnFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'pickup_id',
        'vehicle_loan_id',
        'returner_id',
        'handover_photo_path',
        'handover_photo_name',
        'condition_photo_path',
        'condition_photo_name',
        'incident',
        'incident_note',
        'odometer_start',
        'odometer_end',
        'returned_at',
        'received_by_security_id',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'incident' => 'boolean',
        'returned_at' => 'datetime',
        'odometer_start' => 'integer',
        'odometer_end' => 'integer',
    ];

    public function pickup(): BelongsTo
    {
        return $this->belongsTo(VehicleLoanPickup::class, 'pickup_id');
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(VehicleLoan::class, 'vehicle_loan_id');
    }

    public function returner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returner_id');
    }

    public function security(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_security_id');
    }
}
