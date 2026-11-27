<?php

namespace App\Http\Resources\AuditLogs;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Activitylog\Models\Activity;

/**
 * @mixin Activity
 */
class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'description' => $this->description,
            'log_name' => $this->log_name,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'causer_id' => $this->causer_id,
            'causer_name' => $this->causer instanceof User ? $this->causer->name : null,
            'attribute_changes' => $this->attribute_changes,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
