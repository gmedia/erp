<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MenuResource;
use App\Models\ApprovalRequestStep;
use App\Models\Menu;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $user = Auth::user();

        // Use device name if provided, otherwise default to 'mobile_app'
        $deviceName = $request->post('device_name', 'mobile_app');
        $token = $user->createToken($deviceName);

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => $user->load('employee'),
        ]);
    }

    /**
     * Get the authenticated User along with shared app data.
     */
    public function me(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user->load('employee.permissions');
        $employee = $user->employee;

        // Get company settings
        $companyName = Setting::get('company_name') ?? config('app.name', 'Laravel');
        $logoPath = Setting::get('company_logo_path');
        $companyLogoUrl = (! is_string($logoPath) || $logoPath === '')
            ? null
            : Storage::disk(config('filesystems.default'))->url($logoPath);

        $regionalSettings = [
            'currency' => Setting::get('currency', 'IDR'),
            'number_format_decimal' => Setting::get('number_format_decimal', ','),
            'number_format_thousand' => Setting::get('number_format_thousand', '.'),
            'number_format_hide_decimal' => (bool) Setting::get('number_format_hide_decimal', false),
        ];

        // Get Pending Approvals
        $pendingApprovalsCount = 0;
        if ($user->id) {
            $pendingApprovalsCount = ApprovalRequestStep::pendingInboxForUser($user->id)
                ->count();
        }

        // Get Menus
        $menus = [];
        if ($employee) {
            $permissionIds = $employee->permissions()->pluck('permissions.id')->toArray();
            $allowedMenus = Menu::with(['children' => function ($query) use ($permissionIds) {
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

            $menus = MenuResource::collection($allowedMenus)->resolve();
        }

        // Get Translations
        $locale = app()->getLocale();
        $path = lang_path("{$locale}.json");
        $translations = [];
        if (File::exists($path)) {
            $translations = json_decode(File::get($path), true) ?? [];
        } else {
            $fallbackPath = lang_path('en.json');
            if (File::exists($fallbackPath)) {
                $translations = json_decode(File::get($fallbackPath), true) ?? [];
            }
        }

        return response()->json([
            'user' => $user,
            'employee' => $employee,
            'companyName' => $companyName,
            'companyLogoUrl' => $companyLogoUrl,
            'regionalSettings' => $regionalSettings,
            'menus' => $menus,
            'pendingApprovalsCount' => $pendingApprovalsCount,
            'translations' => $translations,
            'locale' => $locale,
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request...
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Handle an incoming password reset link request.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
                    ? response()->json(['status' => __($status)])
                    : response()->json(['errors' => ['email' => [__($status)]]], 422);
    }

    /**
     * Handle an incoming new password request.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|confirmed|min:8',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));
                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
                    ? response()->json(['status' => __($status)])
                    : response()->json(['errors' => ['email' => [__($status)]]], 422);
    }
}
