<?php

namespace App\Http\Controllers;

use App\Actions\AuditLog\ExportAuditLogAction;
use App\Actions\AuditLog\IndexAuditLogAction;
use App\Http\Requests\AuditLogs\ExportAuditLogRequest;
use App\Http\Requests\AuditLogs\IndexAuditLogRequest;
use App\Http\Resources\AuditLogs\AuditLogCollection;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    public function index(
        IndexAuditLogRequest $request,
        IndexAuditLogAction $action,
    ): AuditLogCollection {
        return new AuditLogCollection($action->execute($request));
    }

    public function export(
        ExportAuditLogRequest $request,
        ExportAuditLogAction $action,
    ): JsonResponse {
        return $action->execute($request);
    }
}
