<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminSettingRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for application-level settings management.
 *
 * Handles display and update of grouped key-value settings
 * such as company info, regional preferences, etc.
 */
class AdminSettingController extends Controller
{
    /**
     * Display the admin settings page.
     */
    public function index(): Response
    {
        $settings = Setting::getGrouped();

        return Inertia::render('admin-settings/index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update admin settings.
     */
    public function update(AdminSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        foreach ($validated as $key => $value) {
            Setting::set($key, $value ?? '');
        }

        return back()->with('success', 'Settings updated successfully.');
    }
}
