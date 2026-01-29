<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $symbol
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit query()
 *
 * @mixin \Eloquent
 */
class Unit extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'symbol',
    ];

    /**
     * Get the products using this unit.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'unit_id');
    }

    /**
     * Get the bill of materials using this unit.
     */
    public function billOfMaterials(): HasMany
    {
        return $this->hasMany(BillOfMaterial::class, 'unit_id');
    }
}
