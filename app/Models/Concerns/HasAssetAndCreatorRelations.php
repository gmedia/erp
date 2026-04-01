<?php

namespace App\Models\Concerns;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasAssetAndCreatorRelations
{
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
