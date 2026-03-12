<?php

namespace Database\Seeders;

use App\Models\ApprovalAuditLog;
use App\Models\ApprovalFlow;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStep;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Pipeline;
use App\Models\PipelineEntityState;
use App\Models\PipelineState;
use App\Models\PipelineStateLog;
use App\Models\PipelineTransition;
use App\Models\PipelineTransitionAction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Supplier;
use App\Models\SupplierCategory;
use App\Models\SupplierReturn;
use App\Models\SupplierReturnItem;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PurchasingSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $adminUserId = User::query()->where('email', config('app.admin'))->value('id') ?? User::query()->value('id');
        $branchId = Branch::query()->value('id');
        $departmentId = Department::query()->value('id');
        $requesterId = Employee::query()->value('id');

        if (! $adminUserId || ! $branchId || ! $departmentId || ! $requesterId) {
            return;
        }

        $supplier = Supplier::query()->first();
        if (! $supplier) {
            $categoryId = SupplierCategory::query()->value('id');
            if (! $categoryId) {
                return;
            }

            $supplier = Supplier::updateOrCreate(
                ['email' => 'purchasing.sample@vendor.local'],
                [
                    'name' => 'Purchasing Sample Vendor',
                    'phone' => '0215551234',
                    'address' => 'Jl. Sample Vendor No. 1',
                    'branch_id' => $branchId,
                    'category_id' => $categoryId,
                    'status' => 'active',
                ]
            );
        }

        $warehouse = Warehouse::query()->first();
        if (! $warehouse) {
            return;
        }

        $products = Product::query()->orderBy('id')->take(6)->get();
        if ($products->isEmpty()) {
            return;
        }

        $fallbackUnitId = Unit::query()->value('id');
        if (! $fallbackUnitId) {
            return;
        }

        $pipelines = $this->seedPurchasingPipelines($adminUserId);

        [$purchaseRequests, $purchaseRequestItems] = $this->seedPurchaseRequests(
            $adminUserId,
            $branchId,
            $departmentId,
            $requesterId,
            $products,
            $fallbackUnitId,
            $pipelines['pr']
        );

        [$purchaseOrders, $purchaseOrderItems] = $this->seedPurchaseOrders(
            $adminUserId,
            $supplier,
            $warehouse,
            $products,
            $fallbackUnitId,
            $purchaseRequestItems,
            $pipelines['po']
        );

        [$goodsReceipts, $goodsReceiptItems] = $this->seedGoodsReceipts(
            $adminUserId,
            $requesterId,
            $warehouse,
            $purchaseOrders,
            $purchaseOrderItems,
            $fallbackUnitId
        );

        $this->seedSupplierReturns(
            $adminUserId,
            $supplier,
            $warehouse,
            $purchaseOrders,
            $goodsReceipts,
            $goodsReceiptItems,
            $fallbackUnitId
        );
    }

    private function seedPurchasingPipelines(int $adminUserId): array
    {
        $prPipeline = Pipeline::firstOrCreate(
            ['code' => 'pr_lifecycle'],
            [
                'name' => 'Purchase Request Lifecycle',
                'entity_type' => 'App\\Models\\PurchaseRequest',
                'description' => 'Lifecycle pipeline for purchase request document',
                'version' => 1,
                'is_active' => true,
                'conditions' => null,
                'created_by' => $adminUserId,
            ]
        );

        $prStates = $this->seedPipelineStates($prPipeline, [
            ['code' => 'draft', 'name' => 'Draft', 'type' => 'initial', 'color' => '#6B7280', 'icon' => 'FileEdit', 'sort_order' => 0],
            ['code' => 'pending_approval', 'name' => 'Pending Approval', 'type' => 'intermediate', 'color' => '#F59E0B', 'icon' => 'Clock', 'sort_order' => 10],
            ['code' => 'approved', 'name' => 'Approved', 'type' => 'intermediate', 'color' => '#10B981', 'icon' => 'CircleCheck', 'sort_order' => 20],
            ['code' => 'rejected', 'name' => 'Rejected', 'type' => 'intermediate', 'color' => '#EF4444', 'icon' => 'CircleX', 'sort_order' => 30],
            ['code' => 'partially_ordered', 'name' => 'Partially Ordered', 'type' => 'intermediate', 'color' => '#3B82F6', 'icon' => 'Boxes', 'sort_order' => 40],
            ['code' => 'fully_ordered', 'name' => 'Fully Ordered', 'type' => 'final', 'color' => '#22C55E', 'icon' => 'CheckCheck', 'sort_order' => 50],
            ['code' => 'cancelled', 'name' => 'Cancelled', 'type' => 'final', 'color' => '#9CA3AF', 'icon' => 'Ban', 'sort_order' => 60],
        ]);

        $prTransitions = $this->seedPipelineTransitions($prPipeline, $prStates, [
            [
                'from' => 'draft',
                'to' => 'pending_approval',
                'name' => 'Submit',
                'code' => 'submit',
                'required_permission' => 'purchase_request.edit',
                'requires_approval' => true,
                'sort_order' => 10,
                'actions' => [
                    ['action_type' => 'trigger_approval', 'execution_order' => 5, 'config' => ['approval_flow_code' => 'purchase_request_default']],
                    ['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'pending_approval']],
                ],
            ],
            [
                'from' => 'pending_approval',
                'to' => 'approved',
                'name' => 'Approve',
                'code' => 'approve',
                'required_permission' => 'purchase_request.edit',
                'requires_approval' => false,
                'sort_order' => 20,
                'actions' => [
                    ['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'approved']],
                ],
            ],
            [
                'from' => 'pending_approval',
                'to' => 'rejected',
                'name' => 'Reject',
                'code' => 'reject',
                'required_permission' => 'purchase_request.edit',
                'requires_comment' => true,
                'sort_order' => 30,
                'actions' => [
                    ['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'rejected']],
                ],
            ],
            [
                'from' => 'rejected',
                'to' => 'draft',
                'name' => 'Revise',
                'code' => 'revise',
                'required_permission' => 'purchase_request.edit',
                'sort_order' => 40,
                'actions' => [
                    ['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'draft']],
                ],
            ],
            [
                'from' => 'approved',
                'to' => 'partially_ordered',
                'name' => 'Partially Ordered',
                'code' => 'partially_ordered',
                'required_permission' => 'purchase_order.create',
                'sort_order' => 50,
                'actions' => [
                    ['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'partially_ordered']],
                ],
            ],
            [
                'from' => 'approved',
                'to' => 'fully_ordered',
                'name' => 'Fully Ordered',
                'code' => 'fully_ordered_direct',
                'required_permission' => 'purchase_order.create',
                'sort_order' => 60,
                'actions' => [
                    ['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'fully_ordered']],
                ],
            ],
            [
                'from' => 'partially_ordered',
                'to' => 'fully_ordered',
                'name' => 'Complete Ordered',
                'code' => 'fully_ordered',
                'required_permission' => 'purchase_order.create',
                'sort_order' => 70,
                'actions' => [
                    ['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'fully_ordered']],
                ],
            ],
            [
                'from' => 'draft',
                'to' => 'cancelled',
                'name' => 'Cancel',
                'code' => 'cancel',
                'required_permission' => 'purchase_request.edit',
                'requires_confirmation' => true,
                'sort_order' => 80,
                'actions' => [
                    ['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'cancelled']],
                ],
            ],
        ]);

        $poPipeline = Pipeline::firstOrCreate(
            ['code' => 'po_lifecycle'],
            [
                'name' => 'Purchase Order Lifecycle',
                'entity_type' => 'App\\Models\\PurchaseOrder',
                'description' => 'Lifecycle pipeline for purchase order document',
                'version' => 1,
                'is_active' => true,
                'conditions' => null,
                'created_by' => $adminUserId,
            ]
        );

        $poStates = $this->seedPipelineStates($poPipeline, [
            ['code' => 'draft', 'name' => 'Draft', 'type' => 'initial', 'color' => '#6B7280', 'icon' => 'FileEdit', 'sort_order' => 0],
            ['code' => 'pending_approval', 'name' => 'Pending Approval', 'type' => 'intermediate', 'color' => '#F59E0B', 'icon' => 'Clock', 'sort_order' => 10],
            ['code' => 'confirmed', 'name' => 'Confirmed', 'type' => 'intermediate', 'color' => '#10B981', 'icon' => 'CircleCheck', 'sort_order' => 20],
            ['code' => 'rejected', 'name' => 'Rejected', 'type' => 'intermediate', 'color' => '#EF4444', 'icon' => 'CircleX', 'sort_order' => 30],
            ['code' => 'partially_received', 'name' => 'Partially Received', 'type' => 'intermediate', 'color' => '#3B82F6', 'icon' => 'Package', 'sort_order' => 40],
            ['code' => 'fully_received', 'name' => 'Fully Received', 'type' => 'intermediate', 'color' => '#22C55E', 'icon' => 'PackageCheck', 'sort_order' => 50],
            ['code' => 'cancelled', 'name' => 'Cancelled', 'type' => 'final', 'color' => '#9CA3AF', 'icon' => 'Ban', 'sort_order' => 60],
            ['code' => 'closed', 'name' => 'Closed', 'type' => 'final', 'color' => '#111827', 'icon' => 'Archive', 'sort_order' => 70],
        ]);

        $poTransitions = $this->seedPipelineTransitions($poPipeline, $poStates, [
            [
                'from' => 'draft',
                'to' => 'pending_approval',
                'name' => 'Submit',
                'code' => 'submit',
                'required_permission' => 'purchase_order.edit',
                'requires_approval' => true,
                'sort_order' => 10,
                'actions' => [
                    ['action_type' => 'trigger_approval', 'execution_order' => 5, 'config' => ['approval_flow_code' => 'purchase_order_default']],
                    ['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'pending_approval']],
                ],
            ],
            [
                'from' => 'draft',
                'to' => 'confirmed',
                'name' => 'Confirm',
                'code' => 'confirm_direct',
                'required_permission' => 'purchase_order.edit',
                'sort_order' => 20,
                'actions' => [
                    ['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'confirmed']],
                ],
            ],
            [
                'from' => 'pending_approval',
                'to' => 'confirmed',
                'name' => 'Approve',
                'code' => 'approve',
                'required_permission' => 'purchase_order.edit',
                'sort_order' => 30,
                'actions' => [
                    ['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'confirmed']],
                ],
            ],
            [
                'from' => 'pending_approval',
                'to' => 'rejected',
                'name' => 'Reject',
                'code' => 'reject',
                'required_permission' => 'purchase_order.edit',
                'requires_comment' => true,
                'sort_order' => 40,
                'actions' => [
                    ['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'rejected']],
                ],
            ],
            [
                'from' => 'rejected',
                'to' => 'draft',
                'name' => 'Revise',
                'code' => 'revise',
                'required_permission' => 'purchase_order.edit',
                'sort_order' => 50,
                'actions' => [
                    ['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'draft']],
                ],
            ],
            [
                'from' => 'confirmed',
                'to' => 'partially_received',
                'name' => 'Partial Receipt',
                'code' => 'partial_receipt',
                'required_permission' => 'goods_receipt.edit',
                'sort_order' => 60,
                'actions' => [
                    ['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'partially_received']],
                ],
            ],
            [
                'from' => 'confirmed',
                'to' => 'fully_received',
                'name' => 'Full Receipt',
                'code' => 'full_receipt_direct',
                'required_permission' => 'goods_receipt.edit',
                'sort_order' => 70,
                'actions' => [
                    ['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'fully_received']],
                ],
            ],
            [
                'from' => 'partially_received',
                'to' => 'fully_received',
                'name' => 'Complete Receipt',
                'code' => 'full_receipt',
                'required_permission' => 'goods_receipt.edit',
                'sort_order' => 80,
                'actions' => [
                    ['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'fully_received']],
                ],
            ],
            [
                'from' => 'confirmed',
                'to' => 'cancelled',
                'name' => 'Cancel',
                'code' => 'cancel',
                'required_permission' => 'purchase_order.edit',
                'requires_confirmation' => true,
                'sort_order' => 90,
                'actions' => [
                    ['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'cancelled']],
                ],
            ],
            [
                'from' => 'fully_received',
                'to' => 'closed',
                'name' => 'Close',
                'code' => 'close',
                'required_permission' => 'purchase_order.edit',
                'sort_order' => 100,
                'actions' => [
                    ['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'closed']],
                ],
            ],
        ]);

        return [
            'pr' => [
                'pipeline' => $prPipeline,
                'states' => $prStates,
                'transitions' => $prTransitions,
            ],
            'po' => [
                'pipeline' => $poPipeline,
                'states' => $poStates,
                'transitions' => $poTransitions,
            ],
        ];
    }

    private function seedPipelineStates(Pipeline $pipeline, array $statesData): array
    {
        $states = [];

        foreach ($statesData as $stateData) {
            $state = PipelineState::firstOrCreate(
                [
                    'pipeline_id' => $pipeline->id,
                    'code' => $stateData['code'],
                ],
                [
                    'pipeline_id' => $pipeline->id,
                    'code' => $stateData['code'],
                    'name' => $stateData['name'],
                    'type' => $stateData['type'],
                    'color' => $stateData['color'],
                    'icon' => $stateData['icon'],
                    'description' => $stateData['name'],
                    'sort_order' => $stateData['sort_order'],
                ]
            );

            $states[$stateData['code']] = $state;
        }

        return $states;
    }

    private function seedPipelineTransitions(Pipeline $pipeline, array $states, array $transitionsData): array
    {
        $transitions = [];

        foreach ($transitionsData as $transitionData) {
            $fromCode = $transitionData['from'];
            $toCode = $transitionData['to'];
            $fromState = $states[$fromCode] ?? null;
            $toState = $states[$toCode] ?? null;

            if (! $fromState || ! $toState) {
                continue;
            }

            $actions = $transitionData['actions'] ?? [];
            unset($transitionData['actions'], $transitionData['from'], $transitionData['to']);

            $transition = PipelineTransition::firstOrCreate(
                [
                    'pipeline_id' => $pipeline->id,
                    'from_state_id' => $fromState->id,
                    'to_state_id' => $toState->id,
                ],
                [
                    'pipeline_id' => $pipeline->id,
                    'from_state_id' => $fromState->id,
                    'to_state_id' => $toState->id,
                    'name' => $transitionData['name'],
                    'code' => $transitionData['code'],
                    'description' => $transitionData['name'],
                    'required_permission' => $transitionData['required_permission'] ?? null,
                    'guard_conditions' => null,
                    'requires_confirmation' => $transitionData['requires_confirmation'] ?? false,
                    'requires_comment' => $transitionData['requires_comment'] ?? false,
                    'requires_approval' => $transitionData['requires_approval'] ?? false,
                    'sort_order' => $transitionData['sort_order'] ?? 0,
                    'is_active' => true,
                ]
            );

            foreach ($actions as $actionData) {
                PipelineTransitionAction::firstOrCreate(
                    [
                        'pipeline_transition_id' => $transition->id,
                        'execution_order' => $actionData['execution_order'],
                    ],
                    [
                        'pipeline_transition_id' => $transition->id,
                        'action_type' => $actionData['action_type'],
                        'execution_order' => $actionData['execution_order'],
                        'config' => $actionData['config'],
                        'is_async' => false,
                        'on_failure' => 'abort',
                        'is_active' => true,
                    ]
                );
            }

            $transitions[$fromCode . '->' . $toCode] = $transition;
        }

        return $transitions;
    }

    private function seedPurchaseRequests(
        int $adminUserId,
        int $branchId,
        int $departmentId,
        int $requesterId,
        $products,
        int $fallbackUnitId,
        array $pipelineConfig
    ): array {
        $year = now()->format('Y');

        $definitions = [
            ['number' => "PR-{$year}-990001", 'status' => 'draft', 'priority' => 'normal'],
            ['number' => "PR-{$year}-990002", 'status' => 'pending_approval', 'priority' => 'high'],
            ['number' => "PR-{$year}-990003", 'status' => 'approved', 'priority' => 'normal'],
            ['number' => "PR-{$year}-990004", 'status' => 'rejected', 'priority' => 'urgent'],
            ['number' => "PR-{$year}-990005", 'status' => 'partially_ordered', 'priority' => 'high'],
            ['number' => "PR-{$year}-990006", 'status' => 'fully_ordered', 'priority' => 'normal'],
            ['number' => "PR-{$year}-990007", 'status' => 'cancelled', 'priority' => 'low'],
        ];

        $histories = [
            'draft' => [],
            'pending_approval' => [['draft', 'pending_approval']],
            'approved' => [['draft', 'pending_approval'], ['pending_approval', 'approved']],
            'rejected' => [['draft', 'pending_approval'], ['pending_approval', 'rejected']],
            'partially_ordered' => [['draft', 'pending_approval'], ['pending_approval', 'approved'], ['approved', 'partially_ordered']],
            'fully_ordered' => [['draft', 'pending_approval'], ['pending_approval', 'approved'], ['approved', 'partially_ordered'], ['partially_ordered', 'fully_ordered']],
            'cancelled' => [['draft', 'cancelled']],
        ];

        $purchaseRequests = [];
        $purchaseRequestItems = [];

        foreach ($definitions as $index => $definition) {
            $status = $definition['status'];
            $approved = in_array($status, ['approved', 'partially_ordered', 'fully_ordered'], true);
            $rejected = $status === 'rejected';

            $firstProduct = $products[$index % $products->count()];
            $secondProduct = $products[($index + 1) % $products->count()];

            $firstQuantity = 12.00;
            $secondQuantity = 8.00;

            $firstUnitPrice = (float) ($firstProduct->cost ?? 10000);
            $secondUnitPrice = (float) ($secondProduct->cost ?? 12000);

            $firstOrdered = 0.00;
            $secondOrdered = 0.00;

            if ($status === 'partially_ordered') {
                $firstOrdered = 6.00;
            }

            if ($status === 'fully_ordered') {
                $firstOrdered = $firstQuantity;
                $secondOrdered = $secondQuantity;
            }

            $estimatedAmount = ($firstQuantity * $firstUnitPrice) + ($secondQuantity * $secondUnitPrice);

            $purchaseRequest = PurchaseRequest::updateOrCreate(
                ['pr_number' => $definition['number']],
                [
                    'branch_id' => $branchId,
                    'department_id' => $departmentId,
                    'requested_by' => $requesterId,
                    'request_date' => now()->subDays(35 - $index)->toDateString(),
                    'required_date' => now()->subDays(28 - $index)->toDateString(),
                    'priority' => $definition['priority'],
                    'status' => $status,
                    'estimated_amount' => $estimatedAmount,
                    'notes' => 'Sample purchasing request data',
                    'approved_by' => $approved ? $adminUserId : null,
                    'approved_at' => $approved ? now()->subDays(30 - $index) : null,
                    'rejection_reason' => $rejected ? 'Budget tidak disetujui pada putaran approval.' : null,
                    'created_by' => $adminUserId,
                ]
            );

            $firstItem = PurchaseRequestItem::updateOrCreate(
                [
                    'purchase_request_id' => $purchaseRequest->id,
                    'product_id' => $firstProduct->id,
                ],
                [
                    'unit_id' => $firstProduct->unit_id ?? $fallbackUnitId,
                    'quantity' => $firstQuantity,
                    'quantity_ordered' => $firstOrdered,
                    'estimated_unit_price' => $firstUnitPrice,
                    'estimated_total' => $firstQuantity * $firstUnitPrice,
                    'notes' => 'Sample PR item A',
                ]
            );

            $secondItem = PurchaseRequestItem::updateOrCreate(
                [
                    'purchase_request_id' => $purchaseRequest->id,
                    'product_id' => $secondProduct->id,
                ],
                [
                    'unit_id' => $secondProduct->unit_id ?? $fallbackUnitId,
                    'quantity' => $secondQuantity,
                    'quantity_ordered' => $secondOrdered,
                    'estimated_unit_price' => $secondUnitPrice,
                    'estimated_total' => $secondQuantity * $secondUnitPrice,
                    'notes' => 'Sample PR item B',
                ]
            );

            $purchaseRequests[$status] = $purchaseRequest;
            $purchaseRequestItems[$status] = [$firstItem, $secondItem];

            $this->syncEntityPipelineState(
                $pipelineConfig,
                'App\\Models\\PurchaseRequest',
                $purchaseRequest->id,
                $status,
                $histories[$status] ?? [],
                $adminUserId
            );

            $approvalRequestStatus = match ($status) {
                'pending_approval' => 'pending',
                'approved', 'partially_ordered', 'fully_ordered' => 'approved',
                'rejected' => 'rejected',
                default => null,
            };

            if ($approvalRequestStatus) {
                $submittedAt = Carbon::parse($purchaseRequest->request_date)->setTime(9, 0);
                $completedAt = $approvalRequestStatus === 'pending'
                    ? null
                    : Carbon::parse($purchaseRequest->request_date)->addDay()->setTime(15, 0);

                $this->syncApprovalRequest(
                    PurchaseRequest::class,
                    $purchaseRequest->id,
                    'purchase_request_default',
                    $approvalRequestStatus,
                    $adminUserId,
                    $submittedAt,
                    $completedAt,
                );
            }
        }

        return [$purchaseRequests, $purchaseRequestItems];
    }

    private function seedPurchaseOrders(
        int $adminUserId,
        Supplier $supplier,
        Warehouse $warehouse,
        $products,
        int $fallbackUnitId,
        array $purchaseRequestItems,
        array $pipelineConfig
    ): array {
        $year = now()->format('Y');

        $definitions = [
            ['number' => "PO-{$year}-990001", 'status' => 'draft', 'source_status' => 'approved'],
            ['number' => "PO-{$year}-990002", 'status' => 'pending_approval', 'source_status' => 'approved'],
            ['number' => "PO-{$year}-990003", 'status' => 'confirmed', 'source_status' => 'approved'],
            ['number' => "PO-{$year}-990004", 'status' => 'rejected', 'source_status' => 'approved'],
            ['number' => "PO-{$year}-990005", 'status' => 'partially_received', 'source_status' => 'partially_ordered'],
            ['number' => "PO-{$year}-990006", 'status' => 'fully_received', 'source_status' => 'fully_ordered'],
            ['number' => "PO-{$year}-990007", 'status' => 'cancelled', 'source_status' => 'approved'],
            ['number' => "PO-{$year}-990008", 'status' => 'closed', 'source_status' => 'fully_ordered'],
        ];

        $histories = [
            'draft' => [],
            'pending_approval' => [['draft', 'pending_approval']],
            'confirmed' => [['draft', 'confirmed']],
            'rejected' => [['draft', 'pending_approval'], ['pending_approval', 'rejected']],
            'partially_received' => [['draft', 'confirmed'], ['confirmed', 'partially_received']],
            'fully_received' => [['draft', 'confirmed'], ['confirmed', 'partially_received'], ['partially_received', 'fully_received']],
            'cancelled' => [['draft', 'confirmed'], ['confirmed', 'cancelled']],
            'closed' => [['draft', 'confirmed'], ['confirmed', 'partially_received'], ['partially_received', 'fully_received'], ['fully_received', 'closed']],
        ];

        $purchaseOrders = [];
        $purchaseOrderItems = [];

        foreach ($definitions as $index => $definition) {
            $status = $definition['status'];
            $sourceItems = $purchaseRequestItems[$definition['source_status']] ?? [];

            $firstProduct = $products[($index + 2) % $products->count()];
            $secondProduct = $products[($index + 3) % $products->count()];

            $firstQuantity = 10.00;
            $secondQuantity = 6.00;

            $firstUnitPrice = (float) ($firstProduct->cost ?? 12000);
            $secondUnitPrice = (float) ($secondProduct->cost ?? 14000);

            $firstReceived = 0.00;
            $secondReceived = 0.00;

            if ($status === 'partially_received') {
                $firstReceived = 4.00;
            }

            if (in_array($status, ['fully_received', 'closed'], true)) {
                $firstReceived = $firstQuantity;
                $secondReceived = $secondQuantity;
            }

            $firstLineTotal = $firstQuantity * $firstUnitPrice;
            $secondLineTotal = $secondQuantity * $secondUnitPrice;
            $subtotal = $firstLineTotal + $secondLineTotal;
            $taxAmount = round($subtotal * 0.11, 2);
            $grandTotal = $subtotal + $taxAmount;

            $approved = in_array($status, ['confirmed', 'partially_received', 'fully_received', 'closed'], true);

            $purchaseOrder = PurchaseOrder::updateOrCreate(
                ['po_number' => $definition['number']],
                [
                    'supplier_id' => $supplier->id,
                    'warehouse_id' => $warehouse->id,
                    'order_date' => now()->subDays(24 - $index)->toDateString(),
                    'expected_delivery_date' => now()->subDays(18 - $index)->toDateString(),
                    'payment_terms' => 'Net 30',
                    'currency' => 'IDR',
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => 0,
                    'grand_total' => $grandTotal,
                    'status' => $status,
                    'notes' => 'Sample purchasing order data',
                    'shipping_address' => 'Gudang Utama',
                    'approved_by' => $approved ? $adminUserId : null,
                    'approved_at' => $approved ? now()->subDays(20 - $index) : null,
                    'created_by' => $adminUserId,
                ]
            );

            $firstSourceItem = $sourceItems[0] ?? null;
            $secondSourceItem = $sourceItems[1] ?? null;

            $firstItem = PurchaseOrderItem::updateOrCreate(
                [
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $firstProduct->id,
                ],
                [
                    'purchase_request_item_id' => $firstSourceItem?->id,
                    'unit_id' => $firstProduct->unit_id ?? $fallbackUnitId,
                    'quantity' => $firstQuantity,
                    'quantity_received' => $firstReceived,
                    'unit_price' => $firstUnitPrice,
                    'discount_percent' => 0,
                    'tax_percent' => 11,
                    'line_total' => $firstLineTotal,
                    'notes' => 'Sample PO item A',
                ]
            );

            $secondItem = PurchaseOrderItem::updateOrCreate(
                [
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $secondProduct->id,
                ],
                [
                    'purchase_request_item_id' => $secondSourceItem?->id,
                    'unit_id' => $secondProduct->unit_id ?? $fallbackUnitId,
                    'quantity' => $secondQuantity,
                    'quantity_received' => $secondReceived,
                    'unit_price' => $secondUnitPrice,
                    'discount_percent' => 0,
                    'tax_percent' => 11,
                    'line_total' => $secondLineTotal,
                    'notes' => 'Sample PO item B',
                ]
            );

            $purchaseOrders[$status] = $purchaseOrder;
            $purchaseOrderItems[$status] = [$firstItem, $secondItem];

            $this->syncEntityPipelineState(
                $pipelineConfig,
                'App\\Models\\PurchaseOrder',
                $purchaseOrder->id,
                $status,
                $histories[$status] ?? [],
                $adminUserId
            );

            $approvalRequestStatus = match ($status) {
                'pending_approval' => 'pending',
                'rejected' => 'rejected',
                default => null,
            };

            if ($approvalRequestStatus) {
                $submittedAt = Carbon::parse($purchaseOrder->order_date)->setTime(10, 0);
                $completedAt = $approvalRequestStatus === 'pending'
                    ? null
                    : Carbon::parse($purchaseOrder->order_date)->addDay()->setTime(16, 0);

                $this->syncApprovalRequest(
                    PurchaseOrder::class,
                    $purchaseOrder->id,
                    'purchase_order_default',
                    $approvalRequestStatus,
                    $adminUserId,
                    $submittedAt,
                    $completedAt,
                );
            }
        }

        return [$purchaseOrders, $purchaseOrderItems];
    }

    private function syncApprovalRequest(
        string $approvableType,
        int $approvableId,
        string $approvalFlowCode,
        string $requestStatus,
        int $submittedBy,
        Carbon $submittedAt,
        ?Carbon $completedAt = null,
    ): void {
        $flow = ApprovalFlow::with('steps')->where('code', $approvalFlowCode)->first();

        if (! $flow || $flow->steps->isEmpty()) {
            return;
        }

        $firstStepOrder = (int) $flow->steps->min('step_order');
        $lastStepOrder = (int) $flow->steps->max('step_order');

        $approvalRequest = ApprovalRequest::updateOrCreate(
            [
                'approvable_type' => $approvableType,
                'approvable_id' => $approvableId,
                'approval_flow_id' => $flow->id,
            ],
            [
                'current_step_order' => $requestStatus === 'pending' ? $firstStepOrder : $lastStepOrder,
                'status' => $requestStatus,
                'submitted_by' => $submittedBy,
                'submitted_at' => $submittedAt,
                'completed_at' => in_array(
                    $requestStatus,
                    ['approved', 'rejected'],
                    true,
                ) ? ($completedAt ?? $submittedAt) : null,
            ]
        );

        ApprovalAuditLog::updateOrCreate(
            [
                'approval_request_id' => $approvalRequest->id,
                'event' => 'submitted',
            ],
            [
                'approvable_type' => $approvableType,
                'approvable_id' => $approvableId,
                'actor_user_id' => $submittedBy,
                'step_order' => null,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Seeder',
                'metadata' => [
                    'source' => 'PurchasingSampleDataSeeder',
                    'approval_flow_code' => $approvalFlowCode,
                    'status' => $requestStatus,
                ],
            ]
        );

        foreach ($flow->steps as $step) {
            $stepStatus = 'pending';
            $action = null;
            $actedBy = null;
            $comments = null;
            $actedAt = null;

            if ($requestStatus === 'approved') {
                $stepStatus = 'approved';
                $action = 'approve';
                $actedBy = $step->approver_user_id;
                $comments = 'Approved via seeder';
                $actedAt = $completedAt ?? $submittedAt;
            }

            if ($requestStatus === 'rejected' && $step->step_order === $firstStepOrder) {
                $stepStatus = 'rejected';
                $action = 'reject';
                $actedBy = $step->approver_user_id;
                $comments = 'Rejected via seeder';
                $actedAt = $completedAt ?? $submittedAt;
            }

            ApprovalRequestStep::updateOrCreate(
                [
                    'approval_request_id' => $approvalRequest->id,
                    'approval_flow_step_id' => $step->id,
                ],
                [
                    'step_order' => $step->step_order,
                    'status' => $stepStatus,
                    'acted_by' => $actedBy,
                    'action' => $action,
                    'comments' => $comments,
                    'acted_at' => $actedAt,
                ]
            );

            if ($requestStatus === 'approved') {
                ApprovalAuditLog::updateOrCreate(
                    [
                        'approval_request_id' => $approvalRequest->id,
                        'event' => 'step_approved',
                        'step_order' => $step->step_order,
                    ],
                    [
                        'approvable_type' => $approvableType,
                        'approvable_id' => $approvableId,
                        'actor_user_id' => $step->approver_user_id,
                        'ip_address' => '127.0.0.1',
                        'user_agent' => 'Seeder',
                        'metadata' => [
                            'source' => 'PurchasingSampleDataSeeder',
                            'comments' => 'Approved via seeder',
                        ],
                    ]
                );
            }
        }

        if ($requestStatus === 'rejected') {
            $rejectedStep = $flow->steps->firstWhere('step_order', $firstStepOrder);

            ApprovalAuditLog::updateOrCreate(
                [
                    'approval_request_id' => $approvalRequest->id,
                    'event' => 'step_rejected',
                    'step_order' => $firstStepOrder,
                ],
                [
                    'approvable_type' => $approvableType,
                    'approvable_id' => $approvableId,
                    'actor_user_id' => $rejectedStep?->approver_user_id,
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Seeder',
                    'metadata' => [
                        'source' => 'PurchasingSampleDataSeeder',
                        'comments' => 'Rejected via seeder',
                    ],
                ]
            );
        }

        if ($requestStatus === 'approved') {
            $finalApprover = $flow->steps->firstWhere('step_order', $lastStepOrder);

            ApprovalAuditLog::updateOrCreate(
                [
                    'approval_request_id' => $approvalRequest->id,
                    'event' => 'completed',
                ],
                [
                    'approvable_type' => $approvableType,
                    'approvable_id' => $approvableId,
                    'actor_user_id' => $finalApprover?->approver_user_id,
                    'step_order' => $lastStepOrder,
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Seeder',
                    'metadata' => [
                        'source' => 'PurchasingSampleDataSeeder',
                        'approval_flow_code' => $approvalFlowCode,
                    ],
                ]
            );
        }
    }

    private function seedGoodsReceipts(
        int $adminUserId,
        int $requesterId,
        Warehouse $warehouse,
        array $purchaseOrders,
        array $purchaseOrderItems,
        int $fallbackUnitId
    ): array {
        $year = now()->format('Y');

        $definitions = [
            [
                'number' => "GR-{$year}-990001",
                'status' => 'draft',
                'po_status' => 'confirmed',
                'quantity_received' => 2.00,
                'quantity_accepted' => 2.00,
                'quantity_rejected' => 0.00,
            ],
            [
                'number' => "GR-{$year}-990002",
                'status' => 'confirmed',
                'po_status' => 'partially_received',
                'quantity_received' => 4.00,
                'quantity_accepted' => 3.00,
                'quantity_rejected' => 1.00,
            ],
            [
                'number' => "GR-{$year}-990003",
                'status' => 'confirmed',
                'po_status' => 'fully_received',
                'quantity_received' => 10.00,
                'quantity_accepted' => 10.00,
                'quantity_rejected' => 0.00,
            ],
            [
                'number' => "GR-{$year}-990004",
                'status' => 'cancelled',
                'po_status' => 'cancelled',
                'quantity_received' => 1.00,
                'quantity_accepted' => 0.00,
                'quantity_rejected' => 1.00,
            ],
        ];

        $goodsReceipts = [];
        $goodsReceiptItems = [];

        foreach ($definitions as $index => $definition) {
            $purchaseOrder = $purchaseOrders[$definition['po_status']] ?? null;
            $poItems = $purchaseOrderItems[$definition['po_status']] ?? [];
            $poItem = $poItems[0] ?? null;

            if (! $purchaseOrder || ! $poItem) {
                continue;
            }

            $confirmed = $definition['status'] === 'confirmed';

            $goodsReceipt = GoodsReceipt::updateOrCreate(
                ['gr_number' => $definition['number']],
                [
                    'purchase_order_id' => $purchaseOrder->id,
                    'warehouse_id' => $warehouse->id,
                    'receipt_date' => now()->subDays(12 - $index)->toDateString(),
                    'supplier_delivery_note' => 'SJ-' . now()->format('Ymd') . '-99' . ($index + 1),
                    'status' => $definition['status'],
                    'notes' => 'Sample goods receipt data',
                    'received_by' => $requesterId,
                    'confirmed_by' => $confirmed ? $adminUserId : null,
                    'confirmed_at' => $confirmed ? now()->subDays(10 - $index) : null,
                    'created_by' => $adminUserId,
                ]
            );

            $goodsReceiptItem = GoodsReceiptItem::updateOrCreate(
                [
                    'goods_receipt_id' => $goodsReceipt->id,
                    'purchase_order_item_id' => $poItem->id,
                ],
                [
                    'product_id' => $poItem->product_id,
                    'unit_id' => $poItem->unit_id ?? $fallbackUnitId,
                    'quantity_received' => $definition['quantity_received'],
                    'quantity_accepted' => $definition['quantity_accepted'],
                    'quantity_rejected' => $definition['quantity_rejected'],
                    'unit_price' => $poItem->unit_price,
                    'notes' => 'Sample GR item',
                ]
            );

            $goodsReceipts[$definition['status'] . '_' . ($index + 1)] = $goodsReceipt;
            $goodsReceiptItems[$definition['status'] . '_' . ($index + 1)] = $goodsReceiptItem;
        }

        return [$goodsReceipts, $goodsReceiptItems];
    }

    private function seedSupplierReturns(
        int $adminUserId,
        Supplier $supplier,
        Warehouse $warehouse,
        array $purchaseOrders,
        array $goodsReceipts,
        array $goodsReceiptItems,
        int $fallbackUnitId
    ): void {
        $year = now()->format('Y');

        $baseReceipt = $goodsReceipts['confirmed_2'] ?? $goodsReceipts['confirmed_3'] ?? null;
        $baseReceiptItem = $goodsReceiptItems['confirmed_2'] ?? $goodsReceiptItems['confirmed_3'] ?? null;
        $baseOrder = $purchaseOrders['fully_received'] ?? $purchaseOrders['partially_received'] ?? null;

        if (! $baseReceipt || ! $baseReceiptItem || ! $baseOrder) {
            return;
        }

        $definitions = [
            ['number' => "SR-{$year}-990001", 'status' => 'draft', 'reason' => 'damaged', 'quantity' => 1.00],
            ['number' => "SR-{$year}-990002", 'status' => 'confirmed', 'reason' => 'defective', 'quantity' => 2.00],
            ['number' => "SR-{$year}-990003", 'status' => 'cancelled', 'reason' => 'other', 'quantity' => 1.00],
        ];

        foreach ($definitions as $index => $definition) {
            $supplierReturn = SupplierReturn::updateOrCreate(
                ['return_number' => $definition['number']],
                [
                    'purchase_order_id' => $baseOrder->id,
                    'goods_receipt_id' => $baseReceipt->id,
                    'supplier_id' => $supplier->id,
                    'warehouse_id' => $warehouse->id,
                    'return_date' => now()->subDays(7 - $index)->toDateString(),
                    'reason' => $definition['reason'],
                    'status' => $definition['status'],
                    'notes' => 'Sample supplier return data',
                    'created_by' => $adminUserId,
                ]
            );

            SupplierReturnItem::updateOrCreate(
                [
                    'supplier_return_id' => $supplierReturn->id,
                    'goods_receipt_item_id' => $baseReceiptItem->id,
                ],
                [
                    'product_id' => $baseReceiptItem->product_id,
                    'unit_id' => $baseReceiptItem->unit_id ?? $fallbackUnitId,
                    'quantity_returned' => $definition['quantity'],
                    'unit_price' => $baseReceiptItem->unit_price,
                    'notes' => 'Sample supplier return item',
                ]
            );
        }
    }

    private function syncEntityPipelineState(
        array $pipelineConfig,
        string $entityType,
        int $entityId,
        string $targetStateCode,
        array $history,
        int $adminUserId
    ): void {
        $pipeline = $pipelineConfig['pipeline'];
        $states = $pipelineConfig['states'];
        $transitions = $pipelineConfig['transitions'];

        $initialState = $states['draft'] ?? null;
        $targetState = $states[$targetStateCode] ?? null;

        if (! $initialState || ! $targetState) {
            return;
        }

        $entityState = PipelineEntityState::updateOrCreate(
            [
                'pipeline_id' => $pipeline->id,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ],
            [
                'current_state_id' => $targetState->id,
                'last_transitioned_by' => $adminUserId,
                'last_transitioned_at' => now(),
                'metadata' => null,
            ]
        );

        PipelineStateLog::query()
            ->where('pipeline_entity_state_id', $entityState->id)
            ->delete();

        $baseDays = max(2, count($history) + 2);

        PipelineStateLog::create([
            'pipeline_entity_state_id' => $entityState->id,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'from_state_id' => null,
            'to_state_id' => $initialState->id,
            'transition_id' => null,
            'performed_by' => $adminUserId,
            'comment' => 'Initial pipeline assignment',
            'created_at' => now()->subDays($baseDays),
        ]);

        $step = $baseDays - 1;
        foreach ($history as $transitionStep) {
            [$fromCode, $toCode] = $transitionStep;

            $fromState = $states[$fromCode] ?? null;
            $toState = $states[$toCode] ?? null;
            $transition = $transitions[$fromCode . '->' . $toCode] ?? null;

            if (! $fromState || ! $toState) {
                continue;
            }

            PipelineStateLog::create([
                'pipeline_entity_state_id' => $entityState->id,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'from_state_id' => $fromState->id,
                'to_state_id' => $toState->id,
                'transition_id' => $transition?->id,
                'performed_by' => $adminUserId,
                'comment' => null,
                'created_at' => now()->subDays(max(1, $step)),
            ]);

            $step--;
        }
    }
}
