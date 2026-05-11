<?php

namespace App\Http\Resources\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait BuildsAuditStampResourceData
{
    /**
     * @return array<string, mixed>
     */
    protected function auditStampFields(Model $model): array
    {
        /** @var User|null $creator */
        $creator = $model->getAttribute('creator');
        /** @var User|null $confirmer */
        $confirmer = $model->getAttribute('confirmer');

        return [
            'created_by' => $model->getAttribute('created_by') ? [
                'id' => $model->getAttribute('created_by'),
                'name' => $creator?->name,
            ] : null,
            'confirmed_by' => $model->getAttribute('confirmed_by') ? [
                'id' => $model->getAttribute('confirmed_by'),
                'name' => $confirmer?->name,
            ] : null,
            'confirmed_at' => $model->getAttribute('confirmed_at')?->toIso8601String(),
            'created_at' => $model->getAttribute('created_at')?->toIso8601String(),
            'updated_at' => $model->getAttribute('updated_at')?->toIso8601String(),
        ];
    }
}
