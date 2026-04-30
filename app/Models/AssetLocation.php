<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $branch_id
 * @property int|null $parent_id
 * @property string $code
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Asset> $assets
 * @property-read int|null $assets_count
 * @property-read \App\Models\Branch $branch
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AssetLocation> $children
 * @property-read int|null $children_count
 * @property-read AssetLocation|null $parent
 *
 * @method static \Database\Factories\AssetLocationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetLocation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetLocation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetLocation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetLocation whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetLocation whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetLocation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetLocation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetLocation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetLocation whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetLocation whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class AssetLocation extends Model
{
    /** @use HasFactory<\Database\Factories\AssetLocationFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'branch_id',
        'parent_id',
        'code',
        'name',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'branch_id' => 'integer',
        'parent_id' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(AssetLocation::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(AssetLocation::class, 'parent_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
