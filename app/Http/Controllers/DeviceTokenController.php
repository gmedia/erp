<?php

namespace App\Http\Controllers;

use App\Actions\DeviceTokens\RegisterDeviceTokenAction;
use App\Actions\DeviceTokens\RevokeDeviceTokenAction;
use App\Http\Requests\DeviceTokens\StoreDeviceTokenRequest;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->deviceTokens()->latest()->get(),
        ]);
    }

    public function store(StoreDeviceTokenRequest $request, RegisterDeviceTokenAction $action): JsonResponse
    {
        $deviceToken = $action->execute($request->user(), $request->validated());

        return response()->json(['data' => $deviceToken], 201);
    }

    public function destroy(DeviceToken $deviceToken, RevokeDeviceTokenAction $action): JsonResponse
    {
        $this->authorize('delete', $deviceToken);

        $action->execute($deviceToken);

        return response()->json(null, 204);
    }
}
