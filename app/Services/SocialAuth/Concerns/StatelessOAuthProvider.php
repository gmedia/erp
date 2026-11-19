<?php

namespace App\Services\SocialAuth\Concerns;

use App\Services\SocialAuth\OAuthStateManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;

/**
 * Makes Socialite's OAuth2 provider stateless for API routes.
 *
 * Socialite's default state and PKCE storage use sessions, which are not
 * started on API routes. The state is signed by OAuthStateManager; the
 * PKCE verifier is stored in cache keyed by that state's nonce.
 */
trait StatelessOAuthProvider
{
    private ?string $stateForRedirect = null;

    /**
     * Build the provider URL without touching the session.
     */
    public function redirect()
    {
        return new RedirectResponse($this->getAuthUrl($this->stateForRedirect));
    }

    public function setStateForRedirect(string $state): self
    {
        $this->stateForRedirect = $state;

        return $this;
    }

    protected function hasInvalidState(): bool
    {
        return false; // Verified explicitly by OAuthStateManager in the callback.
    }

    /**
     * @param  string|null  $state
     * @return array<string, mixed>
     */
    protected function getCodeFields($state = null): array
    {
        $fields = parent::getCodeFields($state);
        $fields['state'] = $state;

        return $fields;
    }

    /**
     * @param  string  $code
     * @return array<string, mixed>
     */
    protected function getTokenFields($code): array
    {
        $fields = parent::getTokenFields($code);

        $verifier = App::make(OAuthStateManager::class)
            ->pullVerifierForExchange((string) request()->input('state'));

        if ($verifier !== null) {
            $fields['code_verifier'] = $verifier;
        }

        return $fields;
    }
}
