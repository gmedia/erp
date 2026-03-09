<?php

namespace App\Http\Resources\ApprovalAuditTrail;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ApprovalAuditTrailCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var class-string
     */
    public $collects = ApprovalAuditTrailResource::class;
}
