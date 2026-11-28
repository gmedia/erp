<?php

namespace App\Http\Resources\MobileAppReleases;

use App\Models\MobileAppRelease;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MobileAppRelease
 */
class MobileAppReleaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'version_name' => $this->version_name,
            'version_code' => $this->version_code,
            'min_supported_version_code' => $this->min_supported_version_code,
            'notes' => $this->notes,
            'file_size' => $this->file_size,
            'sha256' => $this->sha256,
            'is_active' => $this->is_active,
            'uploaded_by' => $this->uploaded_by,
            'uploader_name' => $this->uploader?->name,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
