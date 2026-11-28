<?php

namespace App\Http\Controllers;

use App\Actions\MobileAppReleases\IndexMobileAppReleaseAction;
use App\Actions\MobileAppReleases\SetMobileAppReleaseActiveAction;
use App\Actions\MobileAppReleases\StoreMobileAppReleaseAction;
use App\Http\Requests\MobileAppReleases\IndexMobileAppReleaseRequest;
use App\Http\Requests\MobileAppReleases\StoreMobileAppReleaseRequest;
use App\Http\Resources\MobileAppReleases\MobileAppReleaseCollection;
use App\Http\Resources\MobileAppReleases\MobileAppReleaseResource;
use App\Models\MobileAppRelease;
use Illuminate\Http\JsonResponse;

class MobileAppReleaseController extends Controller
{
    public function index(
        IndexMobileAppReleaseRequest $request,
        IndexMobileAppReleaseAction $action,
    ): MobileAppReleaseCollection {
        return new MobileAppReleaseCollection($action->execute($request));
    }

    public function store(
        StoreMobileAppReleaseRequest $request,
        StoreMobileAppReleaseAction $action,
    ): JsonResponse {
        $release = $action->execute($request);

        return (new MobileAppReleaseResource($release))
            ->response()
            ->setStatusCode(201);
    }

    public function activate(
        MobileAppRelease $mobileAppRelease,
        SetMobileAppReleaseActiveAction $action,
    ): MobileAppReleaseResource {
        return new MobileAppReleaseResource($action->execute($mobileAppRelease, true));
    }

    public function deactivate(
        MobileAppRelease $mobileAppRelease,
        SetMobileAppReleaseActiveAction $action,
    ): MobileAppReleaseResource {
        return new MobileAppReleaseResource($action->execute($mobileAppRelease, false));
    }
}
