<?php

namespace App\Providers;

use App\Models\Setting;
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
