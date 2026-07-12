<?php

namespace App\Models;

use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Represents an employee identity in the system.
 *
 * Employment history (department, position, branch, salary, dates, status)
 * is stored in the Employment model, allowing multiple employment records
 * across companies over time.
 *
 * @property int $id
 * @property string $employee_id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property int|null $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Employment> $employments
 * @property-read int|null $employments_count
 * @property-read Employment|null $currentEmployment
 * @property-read Collection<int, Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read User|null $user
 * @property-read Carbon|null $tenure
 *
 * @method static \Database\Factories\EmployeeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereUserId($value)
 *
 * @mixin \Eloquent
 */
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'name',
        'email',
        'phone',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var list<string>
     */
    protected $hidden = [
        'salary',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'tenure',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * Check if the employee has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->where('name', $permission)->exists();
    }

    /**
     * All employment records for this employee.
     *
     * @return HasMany<Employment>
     */
    public function employments(): HasMany
    {
        return $this->hasMany(Employment::class);
    }

    /**
     * The employee's current (active) employment record.
     *
     * @return HasOne<Employment>
     */
    public function currentEmployment(): HasOne
    {
        return $this->hasOne(Employment::class)->where('is_current', true);
    }

    /**
     * Get the employee's tenure based on the earliest hire date across all employments.
     *
     * Falls back to the employee's created_at date if no employment records exist.
     */
    public function getTenureAttribute(): ?Carbon
    {
        $earliestHireDate = DB::table('employments')
            ->where('employee_id', $this->id)
            ->min('hire_date');

        $date = $earliestHireDate ?? $this->created_at;

        return $date ? Carbon::parse($date) : null;
    }

    /**
     * The system user account associated with this employee.
     *
     * @return BelongsTo<User, Employee>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
