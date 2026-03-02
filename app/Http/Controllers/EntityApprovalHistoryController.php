<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\ApprovalRequest;

class EntityApprovalHistoryController extends Controller
{
    /**
     * Get the approval history for a specific entity.
     * Maps the entity mapping to the actual model class.
     */
    public function index(string $entityType, string $entityId): JsonResponse
    {
        $entityMap = [
            'asset' => \App\Models\Asset::class,
            // Add more as needed, like: 'purchase-request' => \App\Models\PurchaseRequest::class
        ];

        if (!isset($entityMap[$entityType])) {
            return response()->json(['message' => 'Entity type not supported for approvals.'], 400);
        }

        $modelClass = $entityMap[$entityType];
        $resolvedId = $entityId;

        // If the model uses ULID and the provided ID is not numeric, resolve it
        if (!is_numeric($entityId) && in_array(\Illuminate\Database\Eloquent\Concerns\HasUlids::class, class_uses_recursive($modelClass))) {
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
            'data' => $history
        ]);
    }
}
