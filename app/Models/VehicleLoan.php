<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\VehicleLoanFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $code
 * @property int $requester_id
 * @property int|null $department_id
 * @property int|null $branch_id
 * @property int|null $company_id
 * @property Carbon $start_at
 * @property Carbon $end_at
 * @property string $purpose
 * @property int|null $driver_id
 * @property int|null $vehicle_unit_id
 * @property string $status
 * @property Carbon|null $submitted_at
 * @property Carbon|null $approved_at
 * @property int|null $approved_by
 * @property string|null $rejection_reason
 * @property string|null $review_notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $requester
 * @property-read Department|null $department
 * @property-read Branch|null $branch
 * @property-read Company|null $company
 * @property-read VehicleDriver|null $driver
 * @property-read VehicleUnit|null $vehicleUnit
 * @property-read User|null $approver
 * @property-read VehicleLoanPickup|null $pickup
 * @property-read VehicleLoanReturn|null $returns
 * @property-read Collection<int, ApprovalRequest> $approvalRequests
 * @property-read Collection<int, VehicleLoanActivityLog> $activityLogs
 *
 * @method static \Database\Factories\VehicleLoanFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class VehicleLoan extends Model
{
    /** @use HasFactory<VehicleLoanFactory> */
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'requester_id',
        'department_id',
        'branch_id',
        'company_id',
        'start_at',
        'end_at',
        'purpose',
        'driver_id',
        'vehicle_unit_id',
        'status',
        'submitted_at',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'review_notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(VehicleDriver::class, 'driver_id');
    }

    public function vehicleUnit(): BelongsTo
    {
        return $this->belongsTo(VehicleUnit::class, 'vehicle_unit_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function pickup(): HasOne
    {
        return $this->hasOne(VehicleLoanPickup::class);
    }

    public function returns(): HasOne
    {
        return $this->hasOne(VehicleLoanReturn::class);
    }

    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(VehicleLoanActivityLog::class);
    }
}
