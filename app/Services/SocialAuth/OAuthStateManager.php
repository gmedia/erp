<?php

namespace App\Services\SocialAuth;

use App\Exceptions\SocialAuthException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Stateless OAuth state + PKCE helper.
 *
 * Socialite's built-in state check relies on the session, which is not
 * available on API routes. Providers override hasInvalidState() and use
 * this manager instead.
 */
class OAuthStateManager
{
    private const CACHE_PREFIX = 'oauth_state:';

    public function create(string $provider, string $intent = 'login'): array
    {
        $nonce = Str::random(40);
        $verifier = Str::random(96);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        $payload = implode('|', [$provider, $intent, $nonce]);
        $signature = hash_hmac('sha256', $payload, (string) config('app.key'));
        $state = base64_encode($payload . '|' . $signature);

        Cache::put(
            $this->cacheKey($nonce),
            [
                'provider' => $provider,
                'intent' => $intent,
                'verifier' => $verifier,
            ],
            now()->addSeconds((int) config('social_login.state_ttl_seconds', 300))
        );

        return [
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $challenge,
        ];
    }

    /**
     * @return array{provider: string, intent: string, verifier: string, nonce: string}
     */
    public function verify(string $state, string $expectedProvider): array
    {
        $decoded = base64_decode($state, true);
        if ($decoded === false) {
            throw SocialAuthException::invalidState();
        }

        $parts = explode('|', $decoded);
        if (count($parts) !== 4) {
            throw SocialAuthException::invalidState();
        }

        [$provider, $intent, $nonce, $signature] = $parts;
        $expectedSignature = hash_hmac(
            'sha256',
            implode('|', [$provider, $intent, $nonce]),
            (string) config('app.key')
        );

        if (! hash_equals($expectedSignature, $signature) || $provider !== $expectedProvider) {
            throw SocialAuthException::invalidState();
        }

        $key = $this->cacheKey($nonce);
        $data = Cache::get($key);

        if (! is_array($data) || $data['provider'] !== $expectedProvider) {
            throw SocialAuthException::invalidState();
        }

        // Single-use nonce.
        Cache::forget($key);

        return [
            'provider' => $data['provider'],
            'intent' => $data['intent'],
            'verifier' => $data['verifier'],
            'nonce' => $nonce,
        ];
    }

    public function verifierForNonce(string $nonce): ?string
    {
        $data = Cache::get($this->cacheKey($nonce));

        return is_array($data) ? ($data['verifier'] ?? null) : null;
    }

    public function rememberVerifierForExchange(string $state, string $verifier): void
    {
        Cache::put(
            self::CACHE_PREFIX . 'exchange:' . hash('sha256', $state),
            $verifier,
            now()->addSeconds((int) config('social_login.state_ttl_seconds', 300))
        );
    }

    public function pullVerifierForExchange(string $state): ?string
    {
        $key = self::CACHE_PREFIX . 'exchange:' . hash('sha256', $state);
        $verifier = Cache::get($key);
        Cache::forget($key);

        return is_string($verifier) ? $verifier : null;
    }

    private function cacheKey(string $nonce): string
    {
        return self::CACHE_PREFIX . $nonce;
    }
}
