<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminSettingRequest;
use App\Mail\TestSmtpMail;
use App\Models\Account;
use App\Models\Setting;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

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

        foreach (['asset_disposal_gain_account_id', 'asset_disposal_loss_account_id'] as $key) {
            $accountId = $settings['accounting'][$key] ?? null;
            $labelKey = str_replace('_account_id', '_account_label', $key);
            $settings['accounting'][$key] = $accountId ? (string) $accountId : null;
            $settings['accounting'][$labelKey] = $accountId
                ? Account::query()->whereKey($accountId)->value('name')
                : null;
        }

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

        foreach (['social_login_allowed_domains', 'social_login_allowed_tenants'] as $listKey) {
            if (! array_key_exists($listKey, $validated)) {
                continue;
            }

            $raw = is_string($validated[$listKey]) ? $validated[$listKey] : '';
            $items = array_values(array_filter(
                array_map('trim', explode(',', $raw)),
                fn (string $v): bool => $v !== '',
            ));
            $validated[$listKey] = $items;
        }

        $logoPath = null;

        // Write the logo first: if the object storage rejects it, nothing is saved.
        if ($uploadedLogo instanceof UploadedFile) {
            $logoPath = $this->storeLogoFile($uploadedLogo);
        } elseif (is_string($uploadedLogoSvg) && trim($uploadedLogoSvg) !== '') {
            $logoPath = $this->storeLogoSvg($uploadedLogoSvg);
        }

        if ($logoPath === false) {
            return $this->logoUploadFailedResponse();
        }

        DB::transaction(function () use ($validated, $logoPath): void {
            foreach ($validated as $key => $value) {
                Setting::set($key, $value ?? '');
            }

            if (is_string($logoPath)) {
                Setting::set('company_logo_path', $logoPath);
            }
        });

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
                'message' => 'Failed to send email: '.$e->getMessage(),
                'errors' => ['test_email' => ['Failed to send email: '.$e->getMessage()]],
            ], 422);
        }
    }

    protected function storeLogoFile(UploadedFile $file): string|false
    {
        try {
            return Storage::disk(config('filesystems.default'))->putFile('branding/logos', $file);
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    protected function storeLogoSvg(string $svg): string|false
    {
        $path = 'branding/logos/company-logo-'.Str::uuid().'.svg';

        try {
            $stored = Storage::disk(config('filesystems.default'))->put($path, $svg);
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }

        return $stored === true ? $path : false;
    }

    protected function logoUploadFailedResponse(): JsonResponse
    {
        $message = __('admin_settings.logo_upload_failed');

        return response()->json([
            'message' => $message,
            'errors' => ['company_logo' => [$message]],
        ], 422);
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
