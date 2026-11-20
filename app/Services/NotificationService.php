<?php

namespace App\Services;

use App\Contracts\NotifiableEvent;
use App\Contracts\PushSender;
use App\Models\NotificationPreference;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\GenericAppNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class NotificationService
{
    public function __construct(private readonly PushSender $pushSender)
    {
        // Intentionally empty. Dependencies are assigned via promoted properties.
    }

    /**
     * Dispatch a notifiable event through the configured channels.
     * Safe to call multiple times: a (dedupe_key, recipient) pair is unique.
     */
    public function send(NotifiableEvent $event): void
    {
        $eventKey = $event->eventKey();
        $events = (array) config('notifications.events', []);
        $eventConfig = $events[$eventKey] ?? [];
        $configured = is_array($eventConfig) ? ($eventConfig['channels'] ?? ['database']) : ['database'];

        if (! is_array($configured)) {
            $configured = ['database'];
        }

        $channels = array_values(array_intersect($event->channels(), $configured));
        $payload = $event->payload();

        /** @var Collection<int, User> $audience */
        $audience = $event->audience();

        foreach ($audience as $recipient) {
            foreach ($channels as $channel) {
                if (! $this->isEnabled($recipient, $eventKey, $channel)) {
                    continue;
                }

                $dedupeKey = sprintf(
                    '%s:%s:%d:%s',
                    $eventKey,
                    $event->dedupeIdentity(),
                    $recipient->id,
                    $channel,
                );

                if ($channel === 'database') {
                    DatabaseNotification::firstOrCreate(
                        ['dedupe_key' => $dedupeKey],
                        [
                            'id' => (string) Str::uuid(),
                            'type' => GenericAppNotification::class,
                            'notifiable_type' => $recipient->getMorphClass(),
                            'notifiable_id' => $recipient->id,
                            'data' => [
                                'event_key' => $eventKey,
                                'title' => $payload['title'] ?? $eventKey,
                                'body' => $payload['body'] ?? '',
                                'target_url' => $event->targetUrl(),
                            ],
                        ],
                    );

                    continue;
                }

                if ($channel === 'mail' && $recipient->email) {
                    $existing = DatabaseNotification::where('dedupe_key', $dedupeKey)->exists();

                    if ($existing) {
                        continue;
                    }

                    // Reserve the key before queueing so retries cannot resend.
                    DatabaseNotification::create([
                        'id' => (string) Str::uuid(),
                        'type' => GenericAppNotification::class,
                        'notifiable_type' => $recipient->getMorphClass(),
                        'notifiable_id' => $recipient->id,
                        'data' => ['event_key' => $eventKey, 'channel' => 'mail'],
                        'dedupe_key' => $dedupeKey,
                    ]);

                    Mail::send(new GenericAppNotification(
                        $eventKey,
                        $payload,
                        $event->targetUrl(),
                        $recipient->email,
                        (string) (Setting::get('company_name') ?? config('app.name', 'ERP')),
                    ));
                }

                if ($channel === 'push') {
                    $this->sendPush($recipient, $event, $eventKey, $payload, $dedupeKey);
                }
            }
        }
    }

    private function sendPush(User $recipient, NotifiableEvent $event, string $eventKey, array $payload, string $dedupeKey): void
    {
        $tokens = $recipient->deviceTokens()->get();

        if ($tokens->isEmpty()) {
            return;
        }

        try {
            if (! Cache::add('push:' . $dedupeKey, true, now()->addDay())) {
                return;
            }

            $pushPayload = [
                'event_key' => $eventKey,
                'title' => $payload['title'] ?? $eventKey,
                'body' => $payload['body'] ?? '',
                'target_url' => $event->targetUrl(),
            ];

            foreach ($tokens as $token) {
                $this->pushSender->send($token, $pushPayload);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function isEnabled(User $user, string $eventKey, string $channel): bool
    {
        return ! NotificationPreference::where('user_id', $user->id)
            ->where('event_key', $eventKey)
            ->where('channel', $channel)
            ->where('enabled', false)
            ->exists();
    }
}
