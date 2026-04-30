<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $ulid
 * @property int $branch_id
 * @property string $reference
 * @property \Illuminate\Support\Carbon $planned_at
 * @property \Illuminate\Support\Carbon|null $performed_at
 * @property string $status
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch $branch
 * @property-read \App\Models\User|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AssetStocktakeItem> $items
 * @property-read int|null $items_count
 *
 * @method static \Database\Factories\AssetStocktakeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktake newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktake newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktake query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktake whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktake whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktake whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktake whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktake wherePerformedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktake wherePlannedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktake whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktake whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktake whereUlid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktake whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class AssetStocktake extends Model
{
    /** @use HasFactory<\Database\Factories\AssetStocktakeFactory> */
    use HasFactory, HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ulid',
        'branch_id',
        'reference',
        'planned_at',
        'performed_at',
        'status',
        'created_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'planned_at' => 'datetime',
        'performed_at' => 'datetime',
        'branch_id' => 'integer',
        'created_by' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AssetStocktakeItem::class);
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function uniqueIds(): array
    {
        return ['ulid'];
    }
}
