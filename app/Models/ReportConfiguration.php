<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string $report_type
 * @property array<array-key, mixed>|null $layout_config
 * @property bool $is_active
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ReportSection> $sections
 * @property-read int|null $sections_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportConfiguration active()
 * @method static \Database\Factories\ReportConfigurationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportConfiguration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportConfiguration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportConfiguration ofType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportConfiguration query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportConfiguration whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportConfiguration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportConfiguration whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportConfiguration whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportConfiguration whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportConfiguration whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportConfiguration whereLayoutConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportConfiguration whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportConfiguration whereReportType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportConfiguration whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ReportConfiguration extends Model
{
    /** @use HasFactory<\Database\Factories\ReportConfigurationFactory> */
    use HasFactory;

    public const TYPE_BALANCE_SHEET = 'balance_sheet';

    public const TYPE_INCOME_STATEMENT = 'income_statement';

    public const TYPE_CASH_FLOW = 'cash_flow';

    public const TYPE_TRIAL_BALANCE = 'trial_balance';

    public const TYPE_CUSTOM = 'custom';

    public const TYPES = [
        self::TYPE_BALANCE_SHEET,
        self::TYPE_INCOME_STATEMENT,
        self::TYPE_CASH_FLOW,
        self::TYPE_TRIAL_BALANCE,
        self::TYPE_CUSTOM,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'report_type',
        'layout_config',
        'is_active',
        'created_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'layout_config' => 'array',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ReportSection::class)->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('report_type', $type);
    }
}
