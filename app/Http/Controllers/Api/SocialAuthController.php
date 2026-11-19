<?php

namespace App\Http\Controllers\Api;

use App\Actions\SocialAuth\CreateSocialLoginTicketAction;
use App\Actions\SocialAuth\ResolveSocialUserAction;
use App\Exceptions\SocialAuthException;
use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\SocialLoginTicket;
use App\Models\User;
use App\Services\SocialAuth\OAuthStateManager;
use App\Services\SocialAuth\SocialLoginPolicy;
use App\Services\SocialAuth\StatelessSocialiteDriver;
use App\Services\SocialAuth\StatelessSocialiteFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Throwable;

class SocialAuthController extends Controller
{
    public function __construct(
        private readonly OAuthStateManager $stateManager,
        private readonly ResolveSocialUserAction $resolver,
        private readonly CreateSocialLoginTicketAction $ticketCreator,
        private readonly StatelessSocialiteFactory $socialiteFactory,
    ) {}

    /**
     * Public UI flags. Never expose domain or tenant allowlists here.
     */
    public function policy(): JsonResponse
    {
        return response()->json(SocialLoginPolicy::publicFlags());
    }

    /**
     * Begin a stateless OAuth flow.
     */
    public function redirect(Request $request, string $provider): JsonResponse
    {
        $this->assertProvider($provider);

        if (! SocialLoginPolicy::enabled()) {
            throw new SocialAuthException('social_login_disabled', 403);
        }

        $intent = $request->input('intent') === 'link' ? 'link' : 'login';
        $state = $this->stateManager->create($provider, $intent);

        $this->stateManager->rememberVerifierForExchange($state['state'], $this->verifierFromCache($state['nonce']));

        $driver = $this->statelessDriver($provider);
        $driver->setStateForRedirect($state['state']);

        // code_challenge is injected as a parameter; the signed `state`
        // is supplied via the trait override without touching sessions.
        $url = $driver->with([
            'code_challenge' => $state['code_challenge'],
            'code_challenge_method' => 'S256',
        ])->redirect()->getTargetUrl();

        return response()->json([
            'url' => $url,
            'state' => $state['state'],
        ]);
    }

    /**
     * Provider callback. Never returns tokens in the URL.
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->assertProvider($provider);
        $state = (string) $request->input('state');

        if (! SocialLoginPolicy::enabled()) {
            return redirect()->away($this->spaUrl('/auth/social/callback#error=social_login_disabled'));
        }

        try {
            $data = $this->stateManager->verify($state, $provider);

            $socialUser = $this->statelessDriver($provider)->user();
            $result = $this->resolver->execute($provider, $socialUser);

            $user = $result['user'];
            if ($this->hasTwoFactor($user)) {
                $ticket = $this->ticketCreator->execute(
                    $user,
                    $request,
                    $data['nonce'],
                    $provider,
                    $result['account_type'],
                );

                return redirect()->away($this->spaUrl('/two-factor-challenge?social_ticket=' . urlencode($ticket)));
            }

            $ticket = $this->ticketCreator->execute(
                $user,
                $request,
                $data['nonce'],
                $provider,
                $result['account_type'],
            );

            $fragment = $data['intent'] === 'link' ? 'linked=1' : 'ticket=' . urlencode($ticket);
            $path = $data['intent'] === 'link' ? '/settings/social-accounts' : '/auth/social/callback';

            return redirect()->away($this->spaUrl($path . '#' . $fragment));
        } catch (SocialAuthException $e) {
            Log::channel('security')->warning('social_login.callback_failed', [
                'provider' => $provider,
                'code' => $e->errorCode,
                'ip' => $request->ip(),
            ]);

            return redirect()->away($this->spaUrl('/auth/social/callback#error=' . urlencode($e->errorCode)));
        } catch (Throwable $e) {
            report($e);

            return redirect()->away($this->spaUrl('/auth/social/callback#error=provider_error'));
        }
    }

    /**
     * Exchange a single-use ticket for a Sanctum token.
     */
    public function exchange(Request $request): JsonResponse
    {
        $ticketToken = (string) $request->header('X-Social-Ticket-Token');
        if ($ticketToken === '') {
            throw new SocialAuthException('ticket_invalid', 401);
        }

        $ticket = SocialLoginTicket::consume($ticketToken, $request);
        if (! $ticket) {
            throw new SocialAuthException('ticket_invalid', 401);
        }

        $user = User::query()->findOrFail($ticket->user_id);
        $token = $user->createToken('social_login');

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => $user->load('employee'),
        ]);
    }

    /**
     * List linked social accounts for the authenticated user.
     */
    public function accounts(Request $request): JsonResponse
    {
        return response()->json([
            'accounts' => $request->user()->socialAccounts()->get(),
        ]);
    }

    /**
     * Unlink a social account. Refuse removing the last credential.
     */
    public function unlink(Request $request, int $socialAccount): JsonResponse
    {
        $account = SocialAccount::query()
            ->where('id', $socialAccount)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $user = $request->user();
        if ($user->password === null && $user->socialAccounts()->count() <= 1) {
            throw new SocialAuthException('last_credential', 409);
        }

        $account->delete();

        return response()->json(['message' => 'Social account unlinked.']);
    }

    /**
     * Set a password for an OAuth-only user.
     */
    public function setPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'confirmed', 'min:8'],
        ]);

        $user = $request->user();
        $user->password = Hash::make($validated['password']);
        $user->save();

        return response()->json(['message' => 'Password set successfully.']);
    }

    /**
     * Verify a Fortify 2FA code during a social login.
     */
    public function verifyTwoFactor(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'social_ticket' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $ticket = SocialLoginTicket::consume((string) $validated['social_ticket'], $request);
        if (! $ticket) {
            throw new SocialAuthException('ticket_invalid', 401);
        }

        $user = User::query()->findOrFail($ticket->user_id);
        $verified = app(TwoFactorAuthenticationProvider::class)->verify(
            decrypt($user->two_factor_secret),
            (string) $validated['code']
        );

        if (! $this->hasTwoFactor($user) || ! $verified) {
            throw new SocialAuthException('invalid_two_factor_code', 422);
        }

        $newTicket = $this->ticketCreator->execute(
            $user,
            $request,
            $ticket->state_nonce,
            $ticket->provider,
            $ticket->account_type,
        );

        return response()->json(['ticket' => $newTicket]);
    }

    private function statelessDriver(string $provider): StatelessSocialiteDriver
    {
        return $this->socialiteFactory->make($provider);
    }

    private function assertProvider(string $provider): void
    {
        if (! in_array($provider, ['google', 'microsoft'], true)) {
            throw ValidationException::withMessages(['provider' => 'Unsupported provider.']);
        }
    }

    private function hasTwoFactor(User $user): bool
    {
        return $user->two_factor_secret !== null && $user->two_factor_confirmed_at !== null;
    }

    private function spaUrl(string $path): string
    {
        return rtrim((string) config('app.frontend_url', config('app.url')), '/') . $path;
    }

    /**
     * PKCE verifier is stored in cache by OAuthStateManager::create(); fetch
     * it back via the state string so getTokenFields() can use it.
     */
    private function verifierFromCache(string $nonce): string
    {
        return app(OAuthStateManager::class)->verifierForNonce($nonce) ?? '';
    }
}
