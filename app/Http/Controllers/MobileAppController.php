<?php

namespace App\Http\Controllers;

use App\Actions\MobileAppReleases\GetActiveMobileAppReleaseAction;
use App\Http\Resources\MobileAppReleases\MobileAppReleaseResource;
use App\Http\Resources\MobileAppReleases\PublicMobileAppReleaseResource;
use App\Models\MobileAppRelease;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MobileAppController extends Controller
{
    public function show(
        GetActiveMobileAppReleaseAction $action,
    ): MobileAppReleaseResource|JsonResponse {
        $release = $action->execute();

        if ($release === null) {
            return response()->json(['data' => null]);
        }

        return new MobileAppReleaseResource($release->loadMissing('uploader'));
    }

    public function latest(
        GetActiveMobileAppReleaseAction $action,
    ): PublicMobileAppReleaseResource|JsonResponse {
        $release = $action->execute();

        if ($release === null) {
            return response()->json(['message' => 'No active mobile app release.'], 404);
        }

        return new PublicMobileAppReleaseResource($release);
    }

    public function download(GetActiveMobileAppReleaseAction $action): StreamedResponse
    {
        $release = $action->execute();

        abort_if($release === null, 404);

        $disk = Storage::disk((string) config('filesystems.default', 'local'));

        abort_unless($disk->exists($release->file_path), 404);

        return $disk->download($release->file_path, $this->downloadFilename($release), [
            'Content-Type' => 'application/vnd.android.package-archive',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function downloadFilename(MobileAppRelease $release): string
    {
        $company = Str::slug((string) Setting::get('company_name', config('app.name')), '-');
        $version = (string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $release->version_name);
        $version = trim($version, '-._');

        return sprintf(
            '%s-%s.apk',
            $company !== '' ? $company : 'erp',
            $version !== '' ? $version : (string) $release->version_code,
        );
    }
}
