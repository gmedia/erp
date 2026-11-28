<?php

namespace App\Http\Requests\MobileAppReleases;

use App\Http\Requests\AuthorizedFormRequest;
use App\Models\Setting;

class StoreMobileAppReleaseRequest extends AuthorizedFormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $maxUploadMb = (int) Setting::get('mobile_app_max_upload_mb', 150);

        return [
            'file' => ['required', 'file', 'max:' . ($maxUploadMb * 1024)],
            'version_name' => ['required', 'string', 'max:50'],
            'version_code' => ['required', 'integer', 'min:1', 'unique:mobile_app_releases,version_code'],
            'min_supported_version_code' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file' => 'APK file',
            'version_code' => 'version code',
            'min_supported_version_code' => 'minimum supported version code',
        ];
    }
}
