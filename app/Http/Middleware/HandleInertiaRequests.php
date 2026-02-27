<?php

namespace App\Http\Middleware;

use App\Http\Resources\MenuResource;
use App\Models\Employee;
use App\Models\Menu;
use App\Models\Setting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'companyName' => Setting::get('company_name', config('app.name')),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'locale' => app()->getLocale(),
            'availableLocales' => config('app.available_locales', ['en', 'id']),
            'translations' => $this->getTranslations(),
            'menus' => $this->getMenus(),
        ];
    }

    /**
     * Get menus for sidebar navigation.
     *
     * @return array<int, mixed>
     */
    protected function getMenus(): array
    {
        $user = auth()->user();

        if (!$user) {
            return [];
        }

        // Get employee by user_id
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return [];
        }

        // Get employee's permission IDs
        $permissionIds = $employee->permissions()->pluck('permissions.id')->toArray();

        // Get menus that:
        // 1. Have no permissions (public menus accessible to all authenticated users)
        // 2. OR have at least one of the employee's permissions
        $menus = Menu::with(['children' => function ($query) use ($permissionIds) {
            $query->where(function ($q) use ($permissionIds) {
                $q->whereDoesntHave('permissions')
                    ->orWhereHas('permissions', function ($subQ) use ($permissionIds) {
                        $subQ->whereIn('permissions.id', $permissionIds);
                    });
            });
        }])
            ->whereNull('parent_id')
            ->where(function ($query) use ($permissionIds) {
                $query->whereDoesntHave('permissions')
                    ->orWhereHas('permissions', function ($q) use ($permissionIds) {
                        $q->whereIn('permissions.id', $permissionIds);
                    });
            })
            ->get();

        return MenuResource::collection($menus)->resolve();
    }

    /**
     * Get translations for the current locale.
     *
     * @return array<string, mixed>
     */
    protected function getTranslations(): array
    {
        $locale = app()->getLocale();
        $path = lang_path("{$locale}.json");

        if (File::exists($path)) {
            return json_decode(File::get($path), true) ?? [];
        }

        // Fallback to English if current locale file doesn't exist
        $fallbackPath = lang_path('en.json');
        if (File::exists($fallbackPath)) {
            return json_decode(File::get($fallbackPath), true) ?? [];
        }

        return [];
    }
}
