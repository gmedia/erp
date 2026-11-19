<?php

namespace App\Services\SocialAuth;

use App\Services\SocialAuth\Providers\GoogleProvider;
use App\Services\SocialAuth\Providers\MicrosoftProvider;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Contracts\User;

/**
 * Runtime wrapper that narrows Socialite's provider contract to the
 * stateless API provider we register in AppServiceProvider.
 */
class StatelessSocialiteDriver
{
    public function __construct(public readonly GoogleProvider|MicrosoftProvider $provider)
    {
        //
    }

    public function setStateForRedirect(string $state): self
    {
        $this->provider->setStateForRedirect($state);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function with(array $parameters): self
    {
        $this->provider->with($parameters);

        return $this;
    }

    public function redirect(): RedirectResponse
    {
        return $this->provider->redirect();
    }

    public function user(): User
    {
        return $this->provider->user();
    }
}
