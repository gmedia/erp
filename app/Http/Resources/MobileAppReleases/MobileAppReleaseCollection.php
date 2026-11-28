<?php

namespace App\Http\Resources\MobileAppReleases;

use Illuminate\Http\Resources\Json\ResourceCollection;

class MobileAppReleaseCollection extends ResourceCollection
{
    /**
     * @var class-string
     */
    public $collects = MobileAppReleaseResource::class;
}
