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
    }
}
