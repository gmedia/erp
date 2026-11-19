<?php

namespace App\Providers;

use App\Models\Setting;
use App\Models\User;
use App\Services\SocialAuth\Providers\GoogleProvider;
use App\Services\SocialAuth\Providers\MicrosoftProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Manager\Config;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->bootRateLimiters();
        $this->bootSocialite();

        View::composer('app', function ($view) {
            try {
                $companyName = cache()->remember('app.company_name', 3600, function () {
                    return Setting::get('company_name') ?? config('app.name', 'Laravel');
                });
                $view->with('companyName', $companyName);
            } catch (Throwable $e) {
                // Fallback to config if cache/database fails
                report($e); // Log the error for debugging
                $view->with('companyName', config('app.name', 'Laravel'));
            }
        });

        $this->bootMailSettings();
        $this->enforceProductionDebugMode();
        $this->bootPasswordResetUrl();
    }

    /**
     * Point password reset emails at the SPA reset page.
     *
     * The framework default builds the link with route('password.reset'), which
     * no longer exists now that the frontend is a React SPA. Leaving it unset
     * makes /api/forgot-password fail with RouteNotFoundException.
     */
    protected function bootPasswordResetUrl(): void
    {
        ResetPassword::createUrlUsing(function (User $notifiable, string $token): string {
            $base = rtrim((string) config('app.frontend_url', config('app.url')), '/');

            return $base . '/reset-password?' . http_build_query([
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);
        });
    }

    /**
     * Register Socialite providers and rate limiters for social login.
     */
    protected function bootSocialite(): void
    {
        Socialite::extend('google', function () {
            return new GoogleProvider(
                request(),
                (string) config('services.google.client_id'),
                (string) config('services.google.client_secret'),
                (string) config('services.google.redirect')
            );
        });

        Socialite::extend('microsoft', function () {
            $provider = new MicrosoftProvider(
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

            return $provider->setConfig($config);
        });

        $isTesting = app()->environment('testing') || config('app.disable_rate_limiting');
        foreach (['social-redirect' => 10, 'social-callback' => 30, 'social-exchange' => 20] as $name => $max) {
            $this->registerRateLimiter($name, $max, $isTesting);
        }
    }

    /**
     * Override mail configuration dynamically based on admin settings.
     */
    protected function bootMailSettings(): void
    {
        try {
            if (app()->runningInConsole() && ! app()->runningUnitTests()) {
                return;
            }

            $mailSettings = cache()->remember('app.mail_settings', 3600, function () {
                $keys = [
                    'mail_host',
                    'mail_port',
                    'mail_username',
                    'mail_password',
                    'mail_encryption',
                    'mail_from_address',
                    'mail_from_name',
                ];

                $settings = [];
                foreach ($keys as $key) {
                    $settings[$key] = Setting::get($key);
                }

                return array_filter($settings, fn ($value) => ! is_null($value) && $value !== '');
            });

            if (! empty($mailSettings) && ! $this->usesLoopbackMailHost($mailSettings)) {
                // Force default mailer to smtp to ensure database settings are used
                config(['mail.default' => 'smtp']);

                $this->applyMailSettings($mailSettings);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * A loopback mail host is the seed placeholder, never a real mail server.
     *
     * The smtp settings row is created by migration with 127.0.0.1:2525 defaults and
     * is meant to be replaced by an admin. Until then, forcing the smtp mailer makes
     * every outgoing mail fail with a transport error, so the env mailer is kept.
     *
     * @param  array<string, mixed>  $mailSettings
     */
    protected function usesLoopbackMailHost(array $mailSettings): bool
    {
        $host = strtolower(trim((string) ($mailSettings['mail_host'] ?? '')));

        return in_array($host, ['127.0.0.1', 'localhost', '::1'], true);
    }

    /**
     * Register rate limiters for imports and exports.
     */
    protected function bootRateLimiters(): void
    {
        $isTesting = app()->environment('testing') || config('app.disable_rate_limiting');

        $this->registerRateLimiter('imports', 10, $isTesting);
        $this->registerRateLimiter('exports', 10, $isTesting);
        $this->registerRateLimiter('api', 60, $isTesting);
    }

    /**
     * Register a single rate limiter by name.
     */
    protected function registerRateLimiter(string $name, int $maxPerMinute, bool $isTesting): void
    {
        RateLimiter::for($name, function (Request $request) use ($isTesting, $maxPerMinute) {
            if ($isTesting) {
                return Limit::none();
            }

            return Limit::perMinute($maxPerMinute)->by(
                optional($request->user())->id ?: $request->ip()
            );
        });
    }

    /**
     * Enforce APP_DEBUG=false in production environment.
     */
    protected function enforceProductionDebugMode(): void
    {
        if (app()->environment('production')) {
            config(['app.debug' => false]);
        }
    }

    /**
     * @param  array<string, mixed>  $mailSettings
     */
    protected function applyMailSettings(array $mailSettings): void
    {
        foreach ($this->mailSettingConfigMap() as $settingKey => $configKey) {
            if (! isset($mailSettings[$settingKey])) {
                continue;
            }

            config([$configKey => $mailSettings[$settingKey]]);
        }
    }

    /**
     * @return array<string, string>
     */
    protected function mailSettingConfigMap(): array
    {
        return [
            'mail_host' => 'mail.mailers.smtp.host',
            'mail_port' => 'mail.mailers.smtp.port',
            'mail_username' => 'mail.mailers.smtp.username',
            'mail_password' => 'mail.mailers.smtp.password',
            'mail_encryption' => 'mail.mailers.smtp.encryption',
            'mail_from_address' => 'mail.from.address',
            'mail_from_name' => 'mail.from.name',
        ];
    }
}
