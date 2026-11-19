<?php

namespace App\Actions\SocialAuth;

use App\Exceptions\SocialAuthException;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\SocialAuth\SocialLoginPolicy;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class ResolveSocialUserAction
{
    public function __construct(
        private readonly DetectAccountTypeAction $detector,
    ) {}

    /**
     * @return array{user: User, account_type: string}
     */
    public function execute(string $provider, SocialiteUser $socialUser): array
    {
        $raw = method_exists($socialUser, 'getRaw') ? $socialUser->getRaw() : [];
        $email = strtolower((string) $socialUser->getEmail());
        $providerId = (string) $socialUser->getId();

        if ($email === '' || $providerId === '') {
            throw SocialAuthException::emailUnverified();
        }

        $emailVerified = $raw['email_verified'] ?? $raw['verified_email'] ?? null;
        if ($emailVerified !== true && $emailVerified !== 'true' && $emailVerified !== 1) {
            throw SocialAuthException::emailUnverified();
        }

        $detection = $this->detector->execute($provider, $socialUser);
        $this->detector->authorize($provider, $detection);

        $account = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_account_id', $providerId)
            ->first();

        if ($account) {
            $user = User::query()->find($account->user_id);
            if (! $user) {
                throw SocialAuthException::accountNotRegistered();
            }

            // Re-evaluate policy on every login and refresh stored metadata.
            $account->update([
                'email' => $email,
                'name' => $socialUser->getNickname() ?? $socialUser->getName(),
                'avatar_url' => $socialUser->getAvatar(),
                'account_type' => $detection['account_type'],
                'hosted_domain' => $detection['hosted_domain'],
                'tenant_id' => $detection['tenant_id'],
                'last_login_at' => now(),
            ]);

            return ['user' => $user, 'account_type' => $detection['account_type']];
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user && ! SocialLoginPolicy::jitProvisioningEnabled()) {
            throw SocialAuthException::accountNotRegistered();
        }

        if (! $user) {
            $user = User::create([
                'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: $email,
                'email' => $email,
                'password' => null,
                'email_verified_at' => now(),
            ]);
        }

        SocialAccount::create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_account_id' => $providerId,
            'account_type' => $detection['account_type'],
            'email' => $email,
            'name' => $socialUser->getNickname() ?? $socialUser->getName(),
            'avatar_url' => $socialUser->getAvatar(),
            'hosted_domain' => $detection['hosted_domain'],
            'tenant_id' => $detection['tenant_id'],
            'last_login_at' => now(),
        ]);

        return ['user' => $user, 'account_type' => $detection['account_type']];
    }
}
