<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $report_configuration_id
 * @property int|null $parent_id
 * @property string $code
 * @property string $name
 * @property int $sort_order
 * @property string $section_type
 * @property string|null $account_type_filter
 * @property string|null $account_sub_type_filter
 * @property string $sign_convention
 * @property string|null $formula
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ReportSection> $children
 * @property-read int|null $children_count
 * @property-read ReportSection|null $parent
 * @property-read \App\Models\ReportConfiguration $reportConfiguration
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection active()
 * @method static \Database\Factories\ReportSectionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereAccountSubTypeFilter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereAccountTypeFilter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereFormula($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereReportConfigurationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereSectionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereSignConvention($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ReportSection extends Model
{
    /** @use HasFactory<\Database\Factories\ReportSectionFactory> */
    use HasFactory;

    public const TYPE_HEADER = 'header';

    public const TYPE_DETAIL = 'detail';

    public const TYPE_SUBTOTAL = 'subtotal';

    public const TYPE_TOTAL = 'total';

    public const TYPE_SEPARATOR = 'separator';

    public const SECTION_TYPES = [
        self::TYPE_HEADER,
        self::TYPE_DETAIL,
        self::TYPE_SUBTOTAL,
        self::TYPE_TOTAL,
        self::TYPE_SEPARATOR,
    ];

    public const SIGN_NORMAL = 'normal';

    public const SIGN_REVERSED = 'reversed';

    public const SIGN_CONVENTIONS = [
        self::SIGN_NORMAL,
        self::SIGN_REVERSED,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'report_configuration_id',
        'parent_id',
        'code',
        'name',
        'sort_order',
        'section_type',
        'account_type_filter',
        'account_sub_type_filter',
        'sign_convention',
        'formula',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function reportConfiguration(): BelongsTo
    {
        return $this->belongsTo(ReportConfiguration::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
