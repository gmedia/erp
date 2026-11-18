<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $vehicle_loan_id
 * @property string $event
 * @property int|null $actor_user_id
 * @property array|null $metadata
 *
 * @mixin \Eloquent
 */
class VehicleLoanActivityLog extends Model
{
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vehicle_loan_id',
        'event',
        'actor_user_id',
        'metadata',
        'created_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(VehicleLoan::class, 'vehicle_loan_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
