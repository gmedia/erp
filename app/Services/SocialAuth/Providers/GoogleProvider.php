<?php

namespace App\Services\SocialAuth\Providers;

use App\Services\SocialAuth\Concerns\StatelessOAuthProvider;
use Laravel\Socialite\Two\GoogleProvider as BaseGoogleProvider;
use Laravel\Socialite\Two\User;

class GoogleProvider extends BaseGoogleProvider
{
    use StatelessOAuthProvider;

    /** @var array<string, mixed> */
    private array $idTokenClaims = [];

    /**
     * Capture ID token claims while Socialite exchanges the code.
     *
     * @param  string  $code
     * @return array<string, mixed>
     */
    public function getAccessTokenResponse($code)
    {
        $response = parent::getAccessTokenResponse($code);

        $idToken = $response['id_token'] ?? null;
        if (is_string($idToken)) {
            $claims = $this->decodeIdToken($idToken);
            if ($claims !== []) {
                $this->idTokenClaims = $claims;
            }
        }

        return $response;
    }

    /**
     * @param  string  $token
     * @return User
     */
    public function userFromToken($token)
    {
        $user = parent::userFromToken($token);

        if ($this->idTokenClaims !== []) {
            $raw = $user->getRaw();
            $raw['hd'] = $this->idTokenClaims['hd'] ?? null;
            $raw['email_verified'] = $this->idTokenClaims['email_verified'] ?? false;
            $user->setRaw($raw);
        }

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeIdToken(string $idToken): array
    {
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            return [];
        }

        $payload = base64_decode(strtr($parts[1], '-_', '+/'), true);
        if ($payload === false) {
            return [];
        }

        $claims = json_decode($payload, true);

        return is_array($claims) ? $claims : [];
    }
}
