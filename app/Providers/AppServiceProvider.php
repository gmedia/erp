<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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
        View::composer('app', function ($view) {
            try {
                $companyName = cache()->remember('app.company_name', 3600, function () {
                    return Setting::get('company_name') ?? config('app.name', 'Laravel');
                });
                $view->with('companyName', $companyName);
            } catch (\Throwable $e) {
                // Fallback to config if cache/database fails
                report($e); // Log the error for debugging
                $view->with('companyName', config('app.name', 'Laravel'));
            }
        });

        $this->bootMailSettings();
    }

    /**
     * Override mail configuration dynamically based on admin settings.
     */
    protected function bootMailSettings(): void
    {
        try {
            if (app()->runningInConsole() && !app()->runningUnitTests()) {
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

                return array_filter($settings, fn($value) => !is_null($value) && $value !== '');
            });

            if (!empty($mailSettings)) {
                // Force default mailer to smtp to ensure database settings are used
                config(['mail.default' => 'smtp']);

                if (isset($mailSettings['mail_host'])) {
                    config(['mail.mailers.smtp.host' => $mailSettings['mail_host']]);
                }
                if (isset($mailSettings['mail_port'])) {
                    config(['mail.mailers.smtp.port' => $mailSettings['mail_port']]);
                }
                if (isset($mailSettings['mail_username'])) {
                    config(['mail.mailers.smtp.username' => $mailSettings['mail_username']]);
                }
                if (isset($mailSettings['mail_password'])) {
                    config(['mail.mailers.smtp.password' => $mailSettings['mail_password']]);
                }
                if (isset($mailSettings['mail_encryption'])) {
                    config(['mail.mailers.smtp.encryption' => $mailSettings['mail_encryption']]);
                }
                if (isset($mailSettings['mail_from_address'])) {
                    config(['mail.from.address' => $mailSettings['mail_from_address']]);
                }
                if (isset($mailSettings['mail_from_name'])) {
                    config(['mail.from.name' => $mailSettings['mail_from_name']]);
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
