<?php

namespace App\Services\Push;

use App\Contracts\FcmAccessTokenProvider;
use App\Contracts\PushSender;
use App\Models\DeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FcmPushSender implements PushSender
{
    public function __construct(private readonly FcmAccessTokenProvider $tokens) {}

    public function send(DeviceToken $token, array $payload): void
    {
        $projectId = (string) config('services.fcm.project_id');

        if ($projectId === '') {
            throw new RuntimeException('FCM project id is not configured.');
        }

        $response = Http::withToken($this->tokens->get())
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'token' => $token->token,
                    'notification' => [
                        'title' => (string) ($payload['title'] ?? ''),
                        'body' => (string) ($payload['body'] ?? ''),
                    ],
                    'data' => [
                        'event_key' => (string) ($payload['event_key'] ?? ''),
                        'target_url' => (string) ($payload['target_url'] ?? ''),
                    ],
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'channel_id' => 'default',
                        ],
                    ],
                ],
            ]);

        if ($response->successful()) {
            return;
        }

        $status = $response->json('error.status');
        $details = $response->json('error.details');
        $details = is_array($details) ? $details : [];

        $unregistered = $response->status() === 404
            || $status === 'NOT_FOUND'
            || collect($details)->contains(
                fn ($detail) => is_array($detail) && ($detail['errorCode'] ?? null) === 'UNREGISTERED',
            );

        if ($unregistered) {
            $token->delete();
        }

        Log::warning('fcm.push.failed', [
            'device_token_id' => $token->id,
            'status' => $response->status(),
            'error' => $status,
        ]);
    }
}
