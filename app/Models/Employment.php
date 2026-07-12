<?php

namespace App\Models;

use Database\Factories\EmploymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $employee_id
 * @property int $company_id
 * @property int|null $department_id
 * @property int|null $position_id
 * @property int|null $branch_id
 * @property string|null $salary
 * @property string $hire_date
 * @property string|null $termination_date
 * @property string|null $employment_status
 * @property bool $is_current
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Employee $employee
 * @property-read Company $company
 * @property-read Department|null $department
 * @property-read Position|null $position
 * @property-read Branch|null $branch
 *
 * @method static \Database\Factories\EmploymentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employment query()
 *
 * @mixin \Eloquent
 */
class Employment extends Model
{
    /** @use HasFactory<EmploymentFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'company_id',
        'department_id',
        'position_id',
        'branch_id',
        'salary',
        'hire_date',
        'termination_date',
        'employment_status',
        'is_current',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'hire_date' => 'date',
        'termination_date' => 'date',
        'is_current' => 'boolean',
        'salary' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
