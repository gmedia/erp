<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminSettingRequest;
use App\Mail\TestSmtpMail;
use App\Models\Setting;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

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
    public function index(Request $request): JsonResponse
    {
        $settings = Setting::getGrouped();
        $settings['general']['company_logo_url'] = $this->getCompanyLogoUrl();

        return response()->json(['settings' => $settings]);
    }

    /**
     * Update admin settings.
     */
    public function update(AdminSettingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $uploadedLogo = $request->file('company_logo');
        $uploadedLogoSvg = $validated['company_logo_svg'] ?? null;
        unset($validated['company_logo']);
        unset($validated['company_logo_svg']);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value ?? '');
        }

        if ($uploadedLogo instanceof UploadedFile) {
            $path = $uploadedLogo->store('branding/logos', config('filesystems.default'));
            Setting::set('company_logo_path', $path);
        } elseif (is_string($uploadedLogoSvg) && trim($uploadedLogoSvg) !== '') {
            $path = 'branding/logos/company-logo-' . Str::uuid() . '.svg';
            Storage::disk(config('filesystems.default'))->put($path, $uploadedLogoSvg);
            Setting::set('company_logo_path', $path);
        }

        return response()->json([
            'message' => 'Settings updated successfully.',
            'company_logo_url' => $this->getCompanyLogoUrl(),
        ]);
    }

    /**
     * Send a test SMTP email
     */
    public function testSmtp(Request $request): JsonResponse
    {
        $request->validate([
            'test_email' => ['required', 'email', 'max:255'],
        ]);

        try {
            Mail::to($request->input('test_email'))->send(new TestSmtpMail);

            return response()->json(['message' => 'Test email sent successfully. Please check your inbox.']);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to send email: ' . $e->getMessage(),
                'errors' => ['test_email' => ['Failed to send email: ' . $e->getMessage()]],
            ], 422);
        }
    }

    protected function getCompanyLogoUrl(): ?string
    {
        $logoPath = Setting::get('company_logo_path');

        if (! is_string($logoPath) || $logoPath === '') {
            return null;
        }

        return Storage::disk(config('filesystems.default'))->url($logoPath);
    }
}
