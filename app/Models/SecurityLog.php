<?php

namespace App\Models;

use Database\Factories\SecurityLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $event
 * @property int|null $user_id
 * @property string|null $attempted_email
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array<array-key, mixed>|null $metadata
 * @property Carbon $created_at
 * @property-read User|null $user
 *
 * @method static \Database\Factories\SecurityLogFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecurityLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecurityLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecurityLog query()
 *
 * @mixin \Eloquent
 */
class SecurityLog extends Model
{
    /** @use HasFactory<SecurityLogFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    public const EVENT_LOGIN_SUCCESS = 'login_success';

    public const EVENT_LOGIN_FAILED = 'login_failed';

    public const EVENT_LOGOUT = 'logout';

    public const EVENT_PASSWORD_RESET = 'password_reset';

    public const EVENT_ROLE_CHANGED = 'role_changed';

    public const EVENT_USER_CREATED = 'user_created';

    public const EVENT_USER_DEACTIVATED = 'user_deactivated';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'event',
        'user_id',
        'attempted_email',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
