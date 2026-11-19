<?php

namespace App\Services\Push;

use App\Contracts\FcmAccessTokenProvider;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class GoogleServiceAccountTokenProvider implements FcmAccessTokenProvider
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function get(): string
    {
        $credentialsPath = (string) config('services.fcm.credentials');

        if ($credentialsPath === '' || ! is_file($credentialsPath)) {
            throw new RuntimeException('FCM credentials file is not configured.');
        }

        return Cache::remember('fcm.access_token', 3000, function () use ($credentialsPath): string {
            $credentials = new ServiceAccountCredentials(self::SCOPE, $credentialsPath);
            $token = $credentials->fetchAuthToken();
            $accessToken = $token['access_token'] ?? null;

            if (! is_string($accessToken) || $accessToken === '') {
                throw new RuntimeException('Unable to obtain an FCM access token.');
            }

            return $accessToken;
        });
    }
}
