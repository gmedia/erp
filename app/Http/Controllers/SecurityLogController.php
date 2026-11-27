<?php

namespace App\Http\Controllers;

use App\Actions\SecurityLogs\ExportSecurityLogAction;
use App\Actions\SecurityLogs\IndexSecurityLogAction;
use App\Http\Requests\SecurityLogs\ExportSecurityLogRequest;
use App\Http\Requests\SecurityLogs\IndexSecurityLogRequest;
use App\Http\Resources\SecurityLogs\SecurityLogCollection;
use Illuminate\Http\JsonResponse;

class SecurityLogController extends Controller
{
    public function index(
        IndexSecurityLogRequest $request,
        IndexSecurityLogAction $action,
    ): SecurityLogCollection {
        return new SecurityLogCollection($action->execute($request));
    }

    public function export(
        ExportSecurityLogRequest $request,
        ExportSecurityLogAction $action,
    ): JsonResponse {
        return $action->execute($request);
    }
}
