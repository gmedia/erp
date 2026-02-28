<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $group
 * @property string $key
 * @property string|null $value
 * @property string $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Database\Factories\SettingFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting query()
 *
 * @mixin \Eloquent
 */
class Setting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
    ];

    /**
     * Get a setting value by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return static::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value by key.
     *
     * @param string $key
     * @param mixed $value
     * @return Setting
     */
    public static function set(string $key, mixed $value): Setting
    {
        $setting = static::where('key', $key)->first();

        if (! $setting) {
            throw new \InvalidArgumentException("Setting key [{$key}] does not exist.");
        }

        $setting->update([
            'value' => static::serializeValue($value, $setting->type),
        ]);

        return $setting->fresh();
    }

    /**
     * Get all settings grouped by their group.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getGrouped(): array
    {
        $settings = static::all();

        $grouped = [];
        foreach ($settings as $setting) {
            $grouped[$setting->group][$setting->key] = static::castValue($setting->value, $setting->type);
        }

        return $grouped;
    }

    /**
     * Get all settings for a specific group.
     *
     * @param string $group
     * @return array<string, mixed>
     */
    public static function getByGroup(string $group): array
    {
        $settings = static::where('group', $group)->get();

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = static::castValue($setting->value, $setting->type);
        }

        return $result;
    }

    /**
     * Cast value based on type.
     *
     * @param string|null $value
     * @param string $type
     * @return mixed
     */
    protected static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Serialize value for storage.
     *
     * @param mixed $value
     * @param string $type
     * @return string|null
     */
    protected static function serializeValue(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };
    }
}
