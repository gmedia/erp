<?php

namespace App\Models;

use Database\Factories\CustomerCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Database\Factories\CustomerCategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCategory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class CustomerCategory extends Model
{
    /** @use HasFactory<CustomerCategoryFactory> */
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
