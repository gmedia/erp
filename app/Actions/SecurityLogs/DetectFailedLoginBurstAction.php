<?php

namespace App\Actions\SecurityLogs;

use App\Models\SecurityLog;
use App\Models\Setting;
use App\Models\User;
use App\Notifiables\SecurityLogEvent;
use App\Services\NotificationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Warns admins when one account or IP accumulates repeated failed logins within a window.
 * Dedupe is per (subject, window), so each burst notifies once.
 */
class DetectFailedLoginBurstAction
{
    public const DEFAULT_THRESHOLD = 5;

    public const DEFAULT_WINDOW_MINUTES = 15;

    public const SETTING_THRESHOLD = 'security_log_failed_login_threshold';

    public const SETTING_WINDOW_MINUTES = 'security_log_failed_login_window_minutes';

    public function __construct(private readonly NotificationService $notifications)
    {
        // Intentionally empty. Dependencies are assigned via promoted properties.
    }

    public static function threshold(): int
    {
        $value = (int) Setting::get(self::SETTING_THRESHOLD, self::DEFAULT_THRESHOLD);

        return $value > 0 ? $value : self::DEFAULT_THRESHOLD;
    }

    public static function windowMinutes(): int
    {
        $value = (int) Setting::get(self::SETTING_WINDOW_MINUTES, self::DEFAULT_WINDOW_MINUTES);

        return $value > 0 ? $value : self::DEFAULT_WINDOW_MINUTES;
    }

    /**
     * @return Collection<int, User>
     */
    public static function admins(): Collection
    {
        return User::query()
            ->whereHas('employee.permissions', fn ($q) => $q->where('permissions.name', 'security_log'))
            ->get();
    }

    public function execute(string $email, ?string $ipAddress, ?Carbon $now = null): void
    {
        $now ??= now();
        $threshold = self::threshold();
        $windowStart = $now->copy()->subMinutes(self::windowMinutes());

        $this->checkSubject('email', $email, $threshold, $windowStart, $now);

        if ($ipAddress !== null && $ipAddress !== '') {
            $this->checkSubject('ip', $ipAddress, $threshold, $windowStart, $now);
        }
    }

    private function checkSubject(
        string $type,
        string $value,
        int $threshold,
        Carbon $windowStart,
        Carbon $now,
    ): void {
        $column = $type === 'email' ? 'attempted_email' : 'ip_address';

        $count = SecurityLog::query()
            ->where('event', SecurityLog::EVENT_LOGIN_FAILED)
            ->where($column, $value)
            ->where('created_at', '>=', $windowStart)
            ->count();

        if ($count < $threshold) {
            return;
        }

        // One notification per subject per fixed window (e.g. every 15 minutes).
        $windowMinutes = self::windowMinutes();
        $windowBucket = intdiv(($now->timestamp), $windowMinutes * 60);

        $this->notifications->send(new SecurityLogEvent(
            eventKey: 'security.failed_login_burst',
            dedupeIdentity: sprintf('%s-%s-%d', $type, sha1($value), $windowBucket),
            audience: self::admins(),
            payload: [
                'title' => 'Repeated failed login attempts',
                'body' => sprintf(
                    '%d failed login attempts for %s %s in the last %d minutes.',
                    $count,
                    $type === 'email' ? 'account' : 'IP',
                    $value,
                    $windowMinutes,
                ),
            ],
            targetUrl: '/security-logs',
        ));
    }
}
