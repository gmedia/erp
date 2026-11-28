<?php

namespace App\Actions\MobileAppReleases;

use App\Models\MobileAppRelease;
use Illuminate\Support\Facades\DB;

class SetMobileAppReleaseActiveAction
{
    public function execute(MobileAppRelease $release, bool $active): MobileAppRelease
    {
        return DB::transaction(function () use ($release, $active) {
            if ($active) {
                MobileAppRelease::query()
                    ->whereKeyNot($release->getKey())
                    ->update(['is_active' => false]);
            }

            $release->update(['is_active' => $active]);

            return $release->refresh();
        });
    }
}
