<?php

namespace App\Http\Controllers;

use App\Actions\SystemHealth\GetSystemHealthStatusAction;
use App\Health\SystemHealthRunner;
use Illuminate\Http\JsonResponse;

class SystemHealthController extends Controller
{
    public function index(GetSystemHealthStatusAction $action): JsonResponse
    {
        return response()->json($action->execute());
    }

    public function runNow(SystemHealthRunner $runner, GetSystemHealthStatusAction $action): JsonResponse
    {
        $runner->run();

        return response()->json([
            'message' => __('system_health.check_completed'),
            ...$action->execute(),
        ]);
    }
}
