<?php

namespace App\Actions\SecurityLogs;

use App\Models\SecurityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RecordSecurityLogAction
{
    /**
     * Keys in metadata that must never be persisted, even if a caller passes them.
     */
    private const FORBIDDEN_METADATA_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'remember_token',
        'secret',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function execute(
        string $event,
        ?int $userId = null,
        ?string $attemptedEmail = null,
        array $metadata = [],
        ?Request $request = null,
    ): SecurityLog {
        return SecurityLog::create([
            'event' => $event,
            'user_id' => $userId,
            'attempted_email' => $attemptedEmail !== null ? Str::limit($attemptedEmail, 255, '') : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $this->shortUserAgent($request?->userAgent()),
            'metadata' => $this->sanitizeMetadata($metadata) ?: null,
        ]);
    }

    private function shortUserAgent(?string $userAgent): ?string
    {
        if ($userAgent === null || $userAgent === '') {
            return null;
        }

        return Str::limit($userAgent, 255, '');
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function sanitizeMetadata(array $metadata): array
    {
        return collect($metadata)
            ->reject(fn ($value, string $key) => in_array(Str::lower($key), self::FORBIDDEN_METADATA_KEYS, true))
            ->all();
    }
}
