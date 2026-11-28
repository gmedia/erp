<?php

namespace App\Http\Resources\MobileAppReleases;

use App\Models\MobileAppRelease;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MobileAppRelease
 */
class PublicMobileAppReleaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'version_name' => $this->version_name,
            'version_code' => $this->version_code,
            'min_supported_version_code' => $this->min_supported_version_code,
            'sha256' => $this->sha256,
            'download_page_url' => url('/mobile-app'),
        ];
    }
}
