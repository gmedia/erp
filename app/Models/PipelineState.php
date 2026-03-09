<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $pipeline_id
 * @property string $code
 * @property string $name
 * @property string $type
 * @property string|null $color
 * @property string|null $icon
 * @property string|null $description
 * @property int $sort_order
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Pipeline $pipeline
 *
 * @method static \Database\Factories\PipelineStateFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineState newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineState newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineState query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineState whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineState whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineState whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineState whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineState whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineState whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineState whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineState whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineState wherePipelineId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineState whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineState whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineState whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PipelineState extends Model
{
    use HasFactory;

    protected $fillable = [
        'pipeline_id',
        'code',
        'name',
        'type',
        'color',
        'icon',
        'description',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sort_order' => 'integer',
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }
}
