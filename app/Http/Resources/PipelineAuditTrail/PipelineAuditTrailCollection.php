<?php

namespace App\Http\Resources\PipelineAuditTrail;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|Model
 */
class PipelineAuditTrailCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var class-string
     */
    public $collects = PipelineAuditTrailResource::class;
}
