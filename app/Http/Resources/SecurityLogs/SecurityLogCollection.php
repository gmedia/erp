<?php

namespace App\Http\Resources\SecurityLogs;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SecurityLogCollection extends ResourceCollection
{
    /**
     * @var class-string
     */
    public $collects = SecurityLogResource::class;
}
