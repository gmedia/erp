<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'useful_life_months_default',
    ];

    protected $casts = [
        'useful_life_months_default' => 'integer',
    ];

    public function models(): HasMany
    {
        return $this->hasMany(AssetModel::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
