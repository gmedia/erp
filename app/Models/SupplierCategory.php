<?php

namespace App\Models;

use Database\Factories\SupplierCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Database\Factories\SupplierCategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierCategory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class SupplierCategory extends Model
{
    /** @use HasFactory<SupplierCategoryFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];
}
