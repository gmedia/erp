<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $version_name
 * @property int $version_code
 * @property int $min_supported_version_code
 * @property string|null $notes
 * @property string $file_path
 * @property int $file_size
 * @property string $sha256
 * @property bool $is_active
 * @property int|null $uploaded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $uploader
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobileAppRelease newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobileAppRelease newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobileAppRelease query()
 *
 * @mixin \Eloquent
 */
class MobileAppRelease extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'version_name',
        'version_code',
        'min_supported_version_code',
        'notes',
        'file_path',
        'file_size',
        'sha256',
        'is_active',
        'uploaded_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'version_code' => 'integer',
        'min_supported_version_code' => 'integer',
        'file_size' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Rilis aktif saat ini, bila ada.
     */
    public static function active(): ?self
    {
        return static::query()
            ->where('is_active', true)
            ->orderByDesc('version_code')
            ->first();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
