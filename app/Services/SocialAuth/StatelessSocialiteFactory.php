<?php

namespace App\Services\SocialAuth;

use App\Services\SocialAuth\Providers\GoogleProvider;
use App\Services\SocialAuth\Providers\MicrosoftProvider;
use InvalidArgumentException;
use SocialiteProviders\Manager\Config;

class StatelessSocialiteFactory
{
    public function make(string $provider): StatelessSocialiteDriver
    {
        if ($provider === 'google') {
            $driver = new GoogleProvider(
                request(),
                (string) config('services.google.client_id'),
                (string) config('services.google.client_secret'),
                (string) config('services.google.redirect')
            );
        } elseif ($provider === 'microsoft') {
            $driver = new MicrosoftProvider(
                request(),
                (string) config('services.microsoft.client_id'),
                (string) config('services.microsoft.client_secret'),
                (string) config('services.microsoft.redirect')
            );
            $config = new Config(
                (string) config('services.microsoft.client_id'),
                (string) config('services.microsoft.client_secret'),
                (string) config('services.microsoft.redirect'),
                [
                    'tenant' => (string) config('services.microsoft.tenant', 'common'),
                    'include_tenant_info' => true,
                ]
            );
            $driver->setConfig($config);
        } else {
            throw new InvalidArgumentException('Unsupported social provider.');
        }

        $driver->stateless();

        return new StatelessSocialiteDriver($driver);
    }
}
