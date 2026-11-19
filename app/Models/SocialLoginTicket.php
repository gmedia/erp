<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $token_hash
 * @property int $user_id
 * @property string $state_nonce
 * @property string $provider
 * @property string $account_type
 * @property string $ip_hash
 * @property string $user_agent_hash
 * @property Carbon $expires_at
 * @property Carbon|null $consumed_at
 *
 * @mixin \Eloquent
 */
class SocialLoginTicket extends Model
{
    protected $fillable = [
        'token_hash',
        'user_id',
        'state_nonce',
        'provider',
        'account_type',
        'ip_hash',
        'user_agent_hash',
        'expires_at',
        'consumed_at',
    ];

    protected $hidden = ['token_hash'];

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function hashBinding(?string $value): string
    {
        return hash('sha256', (string) $value);
    }

    /**
     * Atomically consume a ticket. Returns null when invalid, expired,
     * already consumed, or presented from a different IP/user-agent.
     */
    public static function consume(string $token, Request $request): ?self
    {
        $tokenHash = static::hashToken($token);
        $ipHash = static::hashBinding($request->ip());
        $userAgentHash = static::hashBinding($request->userAgent());

        $ticket = static::query()
            ->where('token_hash', $tokenHash)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $ticket) {
            return null;
        }

        if (! hash_equals($ticket->ip_hash, $ipHash) || ! hash_equals($ticket->user_agent_hash, $userAgentHash)) {
            return null;
        }

        $updated = static::query()
            ->where('id', $ticket->id)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        return $updated === 1 ? $ticket : null;
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }
}
