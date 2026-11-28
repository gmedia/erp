<?php

namespace App\Actions\MobileAppReleases;

use App\Models\MobileAppRelease;

class GetActiveMobileAppReleaseAction
{
    public function execute(): ?MobileAppRelease
    {
        return MobileAppRelease::active();
    }
}
