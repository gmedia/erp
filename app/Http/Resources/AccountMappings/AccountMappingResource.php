<?php

namespace App\Http\Resources\AccountMappings;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountMappingResource extends JsonResource
{
    public function toArray($request): array
    {
        $source = $this->resource->sourceAccount;
        $target = $this->resource->targetAccount;

        return [
            'id' => $this->resource->id,
            'source_account_id' => $this->resource->source_account_id,
            'target_account_id' => $this->resource->target_account_id,
            'type' => $this->resource->type,
            'notes' => $this->resource->notes,
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
            'source_account' => $source ? [
                'id' => $source->id,
                'code' => $source->code,
                'name' => $source->name,
                'coa_version_id' => $source->coa_version_id,
                'coa_version' => $source->coaVersion ? [
                    'id' => $source->coaVersion->id,
                    'name' => $source->coaVersion->name,
                    'status' => $source->coaVersion->status,
                ] : null,
            ] : null,
            'target_account' => $target ? [
                'id' => $target->id,
                'code' => $target->code,
                'name' => $target->name,
                'coa_version_id' => $target->coa_version_id,
                'coa_version' => $target->coaVersion ? [
                    'id' => $target->coaVersion->id,
                    'name' => $target->coaVersion->name,
                    'status' => $target->coaVersion->status,
                ] : null,
            ] : null,
        ];
    }
}
