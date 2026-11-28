<?php

namespace App\Actions\MobileAppReleases;

use App\Http\Requests\MobileAppReleases\StoreMobileAppReleaseRequest;
use App\Models\MobileAppRelease;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class StoreMobileAppReleaseAction
{
    /**
     * @var list<string>
     */
    private const ALLOWED_MIME_TYPES = [
        'application/vnd.android.package-archive',
        'application/zip',
        'application/x-zip-compressed',
        'application/octet-stream',
    ];

    public function execute(StoreMobileAppReleaseRequest $request): MobileAppRelease
    {
        $file = $request->file('file');

        if (! $file instanceof UploadedFile) {
            throw ValidationException::withMessages([
                'file' => 'The APK file is required.',
            ]);
        }

        $this->assertValidApk($file);
        $this->assertVersionCodeIsNewer((int) $request->input('version_code'));

        $disk = (string) config('filesystems.default', 'local');
        $path = $file->store('mobile-app-releases', $disk);

        return MobileAppRelease::create([
            'version_name' => $request->string('version_name')->toString(),
            'version_code' => (int) $request->input('version_code'),
            'min_supported_version_code' => (int) $request->input('min_supported_version_code'),
            'notes' => $request->filled('notes') ? $request->string('notes')->toString() : null,
            'file_path' => $path,
            'file_size' => (int) $file->getSize(),
            'sha256' => (string) hash_file('sha256', $file->getRealPath()),
            'is_active' => false,
            'uploaded_by' => $request->user()?->id,
        ]);
    }

    private function assertValidApk(UploadedFile $file): void
    {
        if (strtolower($file->getClientOriginalExtension()) !== 'apk') {
            throw ValidationException::withMessages([
                'file' => 'The uploaded file must use the .apk extension.',
            ]);
        }

        if (! in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES, true)) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded file is not a valid Android package.',
            ]);
        }

        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded file could not be read.',
            ]);
        }

        $magic = fread($handle, 2);
        fclose($handle);

        if ($magic !== 'PK') {
            throw ValidationException::withMessages([
                'file' => 'The uploaded file is not a valid Android package.',
            ]);
        }
    }

    private function assertVersionCodeIsNewer(int $versionCode): void
    {
        $activeVersionCode = MobileAppRelease::query()
            ->where('is_active', true)
            ->max('version_code');

        if ($activeVersionCode === null || $versionCode > (int) $activeVersionCode) {
            return;
        }

        throw ValidationException::withMessages([
            'version_code' => 'The version code must be greater than the active release version code.',
        ]);
    }
}
