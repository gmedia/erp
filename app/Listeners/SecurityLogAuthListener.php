<?php

namespace App\Listeners;

use App\Actions\SecurityLogs\DetectFailedLoginBurstAction;
use App\Actions\SecurityLogs\RecordSecurityLogAction;
use App\Models\SecurityLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Records authentication events. Registered as a subscriber, so it is resolved once at boot:
 * the HTTP request is read at dispatch time (not injected) to stay correct under Octane.
 */
class SecurityLogAuthListener
{
    public function __construct(private readonly RecordSecurityLogAction $recorder)
    {
        // Intentionally empty. Dependencies are assigned via promoted properties.
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        $events->listen(Login::class, [self::class, 'handleLogin']);
        $events->listen(Failed::class, [self::class, 'handleFailed']);
        $events->listen(Logout::class, [self::class, 'handleLogout']);
        $events->listen(PasswordReset::class, [self::class, 'handlePasswordReset']);

        return [];
    }

    public function handleLogin(Login $event): void
    {
        $this->recorder->execute(
            SecurityLog::EVENT_LOGIN_SUCCESS,
            userId: (int) $event->user->getAuthIdentifier(),
            metadata: ['guard' => $event->guard],
            request: request(),
        );
    }

    public function handleFailed(Failed $event): void
    {
        $email = $event->credentials['email'] ?? null;
        $email = is_string($email) ? $email : null;

        $request = request();

        $this->recorder->execute(
            SecurityLog::EVENT_LOGIN_FAILED,
            attemptedEmail: $email,
            metadata: ['guard' => $event->guard],
            request: $request,
        );

        if ($email !== null) {
            app(DetectFailedLoginBurstAction::class)->execute($email, $request->ip());
        }
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user === null) {
            return;
        }

        $this->recorder->execute(
            SecurityLog::EVENT_LOGOUT,
            userId: (int) $event->user->getAuthIdentifier(),
            metadata: ['guard' => $event->guard],
            request: request(),
        );
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        $this->recorder->execute(
            SecurityLog::EVENT_PASSWORD_RESET,
            userId: (int) $event->user->getAuthIdentifier(),
            request: request(),
        );
    }
}
