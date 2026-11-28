<?php

namespace App\Http\Resources\AuditLogs;

use Illuminate\Http\Resources\Json\ResourceCollection;

class AuditLogCollection extends ResourceCollection
{
    /**
     * @var class-string
     */
    public $collects = AuditLogResource::class;
}
