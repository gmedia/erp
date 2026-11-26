<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiErrorException;
use App\Models\ApprovalRequest;
use App\Models\Asset;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Http\JsonResponse;

class EntityApprovalHistoryController extends Controller
{
    /**
     * Get the approval history for a specific entity.
     * Maps the entity mapping to the actual model class.
     */
    public function index(string $entityType, string $entityId): JsonResponse
    {
        $entityMap = [
            'asset' => Asset::class,
            'purchase_order' => PurchaseOrder::class,
            'purchase_request' => PurchaseRequest::class,
        ];

        if (! isset($entityMap[$entityType])) {
            throw ApiErrorException::translated(
                400,
                'entity_approval_history.entity_type_not_supported',
                'entity_approval_history_api.entity_type_not_supported',
            );
        }

        $modelClass = $entityMap[$entityType];
        $resolvedId = $entityId;

        // If the model uses ULID and the provided ID is not numeric, resolve it
        if (
            ! is_numeric($entityId)
            && in_array(HasUlids::class, class_uses_recursive($modelClass))
        ) {
            $instance = $modelClass::where('ulid', $entityId)->first();
            if ($instance) {
                $resolvedId = $instance->id;
            }
        }

        $history = ApprovalRequest::with([
            'submitter:id,name,email',
            'steps' => function ($query) {
                // Order steps by order and ID
                $query->orderBy('step_order', 'asc')->orderBy('id', 'asc');
            },
            'steps.actor:id,name,email',
            'steps.delegator:id,name,email',
            'steps.flowStep', // Include the configuration logic if needed
        ])
            ->where('approvable_type', $modelClass)
            ->where('approvable_id', $resolvedId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $history,
        ]);
    }
}
