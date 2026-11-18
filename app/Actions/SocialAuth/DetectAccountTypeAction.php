<?php

namespace App\Actions\SocialAuth;

use App\Exceptions\SocialAuthException;
use App\Services\SocialAuth\SocialLoginPolicy;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class DetectAccountTypeAction
{
    /**
     * @return array{account_type: string, hosted_domain: string|null, tenant_id: string|null}
     */
    public function execute(string $provider, SocialiteUser $user): array
    {
        $raw = method_exists($user, 'getRaw') ? $user->getRaw() : [];

        if ($provider === 'google') {
            $domain = $this->normalize($raw['hd'] ?? null);

            return [
                'account_type' => $domain !== null ? 'workspace' : 'personal',
                'hosted_domain' => $domain,
                'tenant_id' => null,
            ];
        }

        $tenant = $raw['tenant'] ?? null;
        $tenantId = null;

        if (is_object($tenant)) {
            $tenantId = $this->normalize($tenant->id ?? null);
        } elseif (is_array($tenant)) {
            $tenantId = $this->normalize($tenant['id'] ?? null);
        }

        if ($tenantId === null) {
            $claims = method_exists($user, 'getClaims') ? $user->getClaims() : null;
            if (is_object($claims)) {
                $tenantId = $this->normalize($claims->tid ?? null);
            }
        }

        $consumerTenant = (string) config('social_login.microsoft.consumers_tenant_id');
        $isPersonal = $tenantId === null || strcasecmp($tenantId, $consumerTenant) === 0;

        return [
            'account_type' => $isPersonal ? 'personal' : 'microsoft_365',
            'hosted_domain' => null,
            'tenant_id' => $isPersonal ? null : $tenantId,
        ];
    }

    /**
     * Enforce policy from admin settings. Fail-closed when allowlists are empty.
     */
    public function authorize(string $provider, array $detection): void
    {
        if ($detection['account_type'] === 'personal' && ! SocialLoginPolicy::personalAccountsEnabled()) {
            throw SocialAuthException::personalAccountsDisabled();
        }

        if ($provider === 'google' && $detection['account_type'] === 'workspace') {
            if (! in_array($detection['hosted_domain'], SocialLoginPolicy::allowedDomains(), true)) {
                throw SocialAuthException::domainNotAllowed();
            }
        }

        if ($provider === 'microsoft' && $detection['account_type'] === 'microsoft_365') {
            if (! in_array($detection['tenant_id'], SocialLoginPolicy::allowedTenants(), true)) {
                throw SocialAuthException::tenantNotAllowed();
            }
        }
    }

    private function normalize(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = strtolower(trim($value));

        return $value === '' ? null : $value;
    }
}
