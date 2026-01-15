<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Switch the application locale.
     */
    public function update(Request $request, string $locale): RedirectResponse
    {
        // Validate locale is in available locales
        $availableLocales = config('app.available_locales', ['en', 'id']);

        if (! in_array($locale, $availableLocales)) {
            abort(400, 'Invalid locale');
        }

        // Store locale in session
        session(['locale' => $locale]);

        // Also set a cookie for persistence
        $cookie = cookie('locale', $locale, 60 * 24 * 365); // 1 year

        return redirect()->back()->withCookie($cookie);
    }
}
