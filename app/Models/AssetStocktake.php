<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetStocktake extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'reference',
        'planned_at',
        'performed_at',
        'status',
        'created_by',
    ];

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
}
