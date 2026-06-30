<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
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

            if (! empty($mailSettings)) {
                // Force default mailer to smtp to ensure database settings are used
                config(['mail.default' => 'smtp']);

                $this->applyMailSettings($mailSettings);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Register rate limiters for imports and exports.
     */
    protected function bootRateLimiters(): void
    {
        $isTesting = app()->environment('testing') || config('app.disable_rate_limiting');

        RateLimiter::for('imports', function (Request $request) use ($isTesting) {
            if ($isTesting) {
                return Limit::none();
            }

            return Limit::perMinute(10)->by(
                optional($request->user())->id ?: $request->ip()
            );
        });

        RateLimiter::for('exports', function (Request $request) use ($isTesting) {
            if ($isTesting) {
                return Limit::none();
            }

            return Limit::perMinute(10)->by(
                optional($request->user())->id ?: $request->ip()
            );
        });

        RateLimiter::for('api', function (Request $request) use ($isTesting) {
            if ($isTesting) {
                return Limit::none();
            }

            return Limit::perMinute(60)->by(
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
