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
use Database\Seeders\Concerns\ReopensDemoPeriodClosings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PurchasingSampleDataSeeder extends Seeder
{
    use ReopensDemoPeriodClosings;

    protected function seedDemoData(): void
    {
        $adminUserId = User::query()->where('email', config('app.admin'))->value('id') ?? User::query()->value('id');
        $branchId = Branch::query()->value('id');
        $departmentId = Department::query()->value('id');
        $requesterId = Employee::query()->value('id');

        if (! $adminUserId || ! $branchId || ! $departmentId || ! $requesterId) {
            return;
        }

        $legacySupplier = Supplier::where('email', 'purchasing.sample@vendor.local')->first();

        if ($legacySupplier && ! Supplier::where('email', 'pengadaan@sumber-makmur.example')->exists()) {
            $legacySupplier->update([
                'email' => 'pengadaan@sumber-makmur.example',
                'name' => 'PT Sumber Makmur Sejahtera',
                'address' => 'Jl. Industri Raya No. 12, Tangerang',
            ]);
        }

        // Beberapa supplier dengan kategori yang cocok (W5-23).
        $supplierSeeds = [
            ['email' => 'pengadaan@sumber-makmur.example', 'name' => 'PT Sumber Makmur Sejahtera', 'category' => 'Perlengkapan Kantor', 'phone' => '0215558001'],
            ['email' => 'sales@nusantara-elektronik.example', 'name' => 'PT Nusantara Elektronik', 'category' => 'Elektronik', 'phone' => '0215552345'],
            ['email' => 'order@mebel-jaya.example', 'name' => 'CV Mebel Jaya Abadi', 'category' => 'Furnitur', 'phone' => '0225553456'],
            ['email' => 'kontak@logistik-cepat.example', 'name' => 'PT Logistik Cepat Nusantara', 'category' => 'Logistik', 'phone' => '0315554567'],
            ['email' => 'it@solusi-digital.example', 'name' => 'PT Solusi Digital Teknologi', 'category' => DemoMasterData::SUPPLIER_CATEGORY_IT_SERVICES, 'phone' => '0215555678'],
        ];

        foreach ($supplierSeeds as $seed) {
            $categoryId = SupplierCategory::query()
                ->where('name', $seed['category'])
                ->value('id')
                ?? SupplierCategory::query()->value('id');

            Supplier::updateOrCreate(
                ['email' => $seed['email']],
                [
                    'name' => $seed['name'],
                    'phone' => $seed['phone'],
                    'address' => 'Jl. Industri Raya No. 12, Tangerang',
                    'branch_id' => $branchId,
                    'category_id' => $categoryId,
                    'status' => 'active',
                ],
            );
        }

        $suppliers = Supplier::query()
            ->whereIn('email', array_column($supplierSeeds, 'email'))
            ->orderBy('id')
            ->get();

        $supplier = $suppliers->first();
        if (! $supplier) {
            $categoryId = SupplierCategory::query()->value('id');
            if (! $categoryId) {
                return;
            }

            $supplier = Supplier::updateOrCreate(
                ['email' => 'pengadaan@sumber-makmur.example'],
                [
                    'name' => 'PT Sumber Makmur Sejahtera',
                    'phone' => '0215558001',
                    'address' => 'Jl. Industri Raya No. 12, Tangerang',
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

        // Barang fisik saja: produk jasa (type=service) tidak menghasilkan penerimaan/stok.
        $products = Product::query()->where('type', '!=', 'service')->orderBy('id')->take(6)->get();
        if ($products->isEmpty()) {
            return;
        }

        $fallbackUnitId = Unit::query()->value('id');
        if (! $fallbackUnitId) {
            return;
        }

        $pipelines = $this->seedPurchasingPipelines($adminUserId);

        // Pengaju dokumen harus user yang bukan approver flow pembelian (W7-16).
        $documentRequesterId = $this->nonApproverUserId($adminUserId);

        [$purchaseRequests, $purchaseRequestItems] = $this->seedPurchaseRequests(
            $adminUserId,
            $branchId,
            $departmentId,
            $requesterId,
            $products,
            $fallbackUnitId,
            $pipelines['pr'],
            $documentRequesterId
        );

        [$purchaseOrders, $purchaseOrderItems] = $this->seedPurchaseOrders(
            $adminUserId,
            $supplier,
            $warehouse,
            $products,
            $fallbackUnitId,
            $purchaseRequestItems,
            $pipelines['po'],
            $documentRequesterId
        );

        [$goodsReceipts, $goodsReceiptItems] = $this->seedGoodsReceipts(
            $adminUserId,
            $requesterId,
            $warehouse,
            $purchaseOrders,
            $purchaseOrderItems,
            $fallbackUnitId
        );

        // Received quantity on purchase order lines must be derived from the
        // confirmed goods receipt lines — the single source of truth used by
        // every purchasing report (W7-03).
        $this->syncPurchaseOrderReceivedQuantities();

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

    /**
     * User pengaju dokumen pembelian: bukan admin dan bukan approver flow pembelian,
     * sehingga pengaju tidak pernah menyetujui dokumennya sendiri (W7-16).
     */
    private function nonApproverUserId(int $adminUserId): int
    {
        $approverIds = DB::table('approval_flow_steps')->pluck('approver_user_id')->unique()->all();

        return (int) (User::query()
            ->whereNotIn('id', array_merge([$adminUserId], $approverIds))
            ->orderBy('id')
            ->value('id') ?? $adminUserId);
    }

    private function seedPurchasingPipelines(int $adminUserId): array
    {
        $prPipeline = Pipeline::firstOrCreate(
            ['code' => 'pr_lifecycle'],
            [
                'name' => 'Siklus Hidup Permintaan Pembelian',
                'entity_type' => 'App\\Models\\PurchaseRequest',
                'description' => 'Lifecycle pipeline for purchase request document',
                'version' => 1,
                'is_active' => true,
                'conditions' => null,
                'created_by' => $adminUserId,
            ]
        );

        $prStates = $this->seedPipelineStates($prPipeline, [
            [
                'code' => 'draft',
                'name' => 'Draft',
                'type' => 'initial',
                'color' => '#6B7280',
                'icon' => 'FileEdit',
                'sort_order' => 0,
            ],
            [
                'code' => 'pending_approval',
                'name' => 'Pending Approval',
                'type' => 'intermediate',
                'color' => '#F59E0B',
                'icon' => 'Clock',
                'sort_order' => 10,
            ],
            [
                'code' => 'approved',
                'name' => 'Approved',
                'type' => 'intermediate',
                'color' => '#10B981',
                'icon' => 'CircleCheck',
                'sort_order' => 20,
            ],
            [
                'code' => 'rejected',
                'name' => 'Rejected',
                'type' => 'intermediate',
                'color' => '#EF4444',
                'icon' => 'CircleX',
                'sort_order' => 30,
            ],
            [
                'code' => 'partially_ordered',
                'name' => 'Partially Ordered',
                'type' => 'intermediate',
                'color' => '#3B82F6',
                'icon' => 'Boxes',
                'sort_order' => 40,
            ],
            [
                'code' => 'fully_ordered',
                'name' => 'Fully Ordered',
                'type' => 'final',
                'color' => '#22C55E',
                'icon' => 'CheckCheck',
                'sort_order' => 50,
            ],
            [
                'code' => 'cancelled',
                'name' => 'Cancelled',
                'type' => 'final',
                'color' => '#9CA3AF',
                'icon' => 'Ban',
                'sort_order' => 60,
            ],
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
                    [
                        'action_type' => 'trigger_approval',
                        'execution_order' => 5,
                        'config' => [
                            'flow_code' => 'purchase_request_default',
                        ],
                    ],
                    [
                        'action_type' => 'update_field',
                        'execution_order' => 10,
                        'config' => [
                            'field' => 'status',
                            'value' => 'pending_approval',
                        ],
                    ],
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
                    [
                        'action_type' => 'update_field',
                        'execution_order' => 10,
                        'config' => [
                            'field' => 'status',
                            'value' => 'approved',
                        ],
                    ],
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
                    [
                        'action_type' => 'update_field',
                        'execution_order' => 10,
                        'config' => [
                            'field' => 'status',
                            'value' => 'rejected',
                        ],
                    ],
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
                    [
                        'action_type' => 'update_field',
                        'execution_order' => 10,
                        'config' => [
                            'field' => 'status',
                            'value' => 'draft',
                        ],
                    ],
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
                    [
                        'action_type' => 'update_field',
                        'execution_order' => 10,
                        'config' => [
                            'field' => 'status',
                            'value' => 'partially_ordered',
                        ],
                    ],
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
                    [
                        'action_type' => 'update_field',
                        'execution_order' => 10,
                        'config' => [
                            'field' => 'status',
                            'value' => 'fully_ordered',
                        ],
                    ],
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
                    [
                        'action_type' => 'update_field',
                        'execution_order' => 10,
                        'config' => [
                            'field' => 'status',
                            'value' => 'fully_ordered',
                        ],
                    ],
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
                    [
                        'action_type' => 'update_field',
                        'execution_order' => 10,
                        'config' => [
                            'field' => 'status',
                            'value' => 'cancelled',
                        ],
                    ],
                ],
            ],
        ]);

        $poPipeline = Pipeline::firstOrCreate(
            ['code' => 'po_lifecycle'],
            [
                'name' => 'Siklus Hidup Pesanan Pembelian',
                'entity_type' => 'App\\Models\\PurchaseOrder',
                'description' => 'Lifecycle pipeline for purchase order document',
                'version' => 1,
                'is_active' => true,
                'conditions' => null,
                'created_by' => $adminUserId,
            ]
        );

        $poStates = $this->seedPipelineStates($poPipeline, [
            [
                'code' => 'draft',
                'name' => 'Draft',
                'type' => 'initial',
                'color' => '#6B7280',
                'icon' => 'FileEdit',
                'sort_order' => 0,
            ],
            [
                'code' => 'pending_approval',
                'name' => 'Pending Approval',
                'type' => 'intermediate',
                'color' => '#F59E0B',
                'icon' => 'Clock',
                'sort_order' => 10,
            ],
            [
                'code' => 'confirmed',
                'name' => 'Confirmed',
                'type' => 'intermediate',
                'color' => '#10B981',
                'icon' => 'CircleCheck',
                'sort_order' => 20,
            ],
            [
                'code' => 'rejected',
                'name' => 'Rejected',
                'type' => 'intermediate',
                'color' => '#EF4444',
                'icon' => 'CircleX',
                'sort_order' => 30,
            ],
            [
                'code' => 'partially_received',
                'name' => 'Partially Received',
                'type' => 'intermediate',
                'color' => '#3B82F6',
                'icon' => 'Package',
                'sort_order' => 40,
            ],
            [
                'code' => 'fully_received',
                'name' => 'Fully Received',
                'type' => 'intermediate',
                'color' => '#22C55E',
                'icon' => 'PackageCheck',
                'sort_order' => 50,
            ],
            [
                'code' => 'cancelled',
                'name' => 'Cancelled',
                'type' => 'final',
                'color' => '#9CA3AF',
                'icon' => 'Ban',
                'sort_order' => 60,
            ],
            [
                'code' => 'closed',
                'name' => 'Closed',
                'type' => 'final',
                'color' => '#111827',
                'icon' => 'Archive',
                'sort_order' => 70,
            ],
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
                    [
                        'action_type' => 'trigger_approval',
                        'execution_order' => 5,
                        'config' => [
                            'flow_code' => 'purchase_order_default',
                        ],
                    ],
                    [
                        'action_type' => 'update_field',
                        'execution_order' => 10,
                        'config' => [
                            'field' => 'status',
                            'value' => 'pending_approval',
                        ],
                    ],
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
                    [
                        'action_type' => 'update_field',
                        'execution_order' => 10,
                        'config' => [
                            'field' => 'status',
                            'value' => 'confirmed',
                        ],
                    ],
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
                    [
                        'action_type' => 'update_field',
                        'execution_order' => 10,
                        'config' => [
                            'field' => 'status',
                            'value' => 'confirmed',
                        ],
                    ],
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
                    [
                        'action_type' => 'update_field',
                        'execution_order' => 10,
                        'config' => [
                            'field' => 'status',
                            'value' => 'rejected',
                        ],
                    ],
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
                    [
                        'action_type' => 'update_field',
                        'execution_order' => 10,
                        'config' => [
                            'field' => 'status',
                            'value' => 'draft',
                        ],
                    ],
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
                    [
                        'action_type' => 'update_field',
                        'execution_order' => 10,
                        'config' => [
                            'field' => 'status',
                            'value' => 'partially_received',
                        ],
                    ],
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
                    [
                        'action_type' => 'update_field',
                        'execution_order' => 10,
                        'config' => [
                            'field' => 'status',
                            'value' => 'fully_received',
                        ],
                    ],
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
                    [
                        'action_type' => 'update_field',
                        'execution_order' => 10,
                        'config' => [
                            'field' => 'status',
                            'value' => 'fully_received',
                        ],
                    ],
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
                    [
                        'action_type' => 'update_field',
                        'execution_order' => 10,
                        'config' => [
                            'field' => 'status',
                            'value' => 'cancelled',
                        ],
                    ],
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
                    [
                        'action_type' => 'update_field',
                        'execution_order' => 10,
                        'config' => [
                            'field' => 'status',
                            'value' => 'closed',
                        ],
                    ],
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
        array $pipelineConfig,
        int $documentRequesterId
    ): array {
        $year = now()->format('Y');

        // Rentang lama 99xxxx → 0001xx (W6-08): rename bila target kosong,
        // hapus duplikat lama bila sudah ada.
        foreach (PurchaseRequest::query()->where('pr_number', 'like', "PR-{$year}-99%")->get() as $legacy) {
            $target = "PR-{$year}-0001" . substr($legacy->pr_number, -2);

            if (PurchaseRequest::query()->where('pr_number', $target)->exists()) {
                $legacy->delete();
            } else {
                $legacy->update(['pr_number' => $target]);
            }
        }

        $definitions = [
            ['number' => "PR-{$year}-000101", 'status' => 'draft', 'priority' => 'normal'],
            ['number' => "PR-{$year}-000102", 'status' => 'pending_approval', 'priority' => 'high'],
            ['number' => "PR-{$year}-000103", 'status' => 'approved', 'priority' => 'normal'],
            ['number' => "PR-{$year}-000104", 'status' => 'rejected', 'priority' => 'urgent'],
            ['number' => "PR-{$year}-000105", 'status' => 'partially_ordered', 'priority' => 'high'],
            ['number' => "PR-{$year}-000106", 'status' => 'fully_ordered', 'priority' => 'normal'],
            ['number' => "PR-{$year}-000107", 'status' => 'cancelled', 'priority' => 'low'],
        ];

        $histories = [
            'draft' => [],
            'pending_approval' => [['draft', 'pending_approval']],
            'approved' => [['draft', 'pending_approval'], ['pending_approval', 'approved']],
            'rejected' => [['draft', 'pending_approval'], ['pending_approval', 'rejected']],
            'partially_ordered' => [
                ['draft', 'pending_approval'],
                ['pending_approval', 'approved'],
                ['approved', 'partially_ordered'],
            ],
            'fully_ordered' => [
                ['draft', 'pending_approval'],
                ['pending_approval', 'approved'],
                ['approved', 'partially_ordered'],
                ['partially_ordered', 'fully_ordered'],
            ],
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
                    'notes' => 'Permintaan pembelian kebutuhan operasional',
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
                    'notes' => 'Kebutuhan operasional gudang',
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
                    'notes' => 'Kebutuhan perawatan fasilitas',
                ]
            );

            $purchaseRequests[$status] = $purchaseRequest;
            $purchaseRequestItems[$status] = [$firstItem, $secondItem];

            $documentDate = Carbon::parse($purchaseRequest->request_date);

            $this->syncEntityPipelineState(
                $pipelineConfig,
                'App\\Models\\PurchaseRequest',
                $purchaseRequest->id,
                $status,
                $histories[$status] ?? [],
                $adminUserId,
                $documentDate,
                $documentRequesterId
            );

            $approvalRequestStatus = match ($status) {
                'pending_approval' => 'pending',
                'approved', 'partially_ordered', 'fully_ordered' => 'approved',
                'rejected' => 'rejected',
                default => null,
            };

            if ($approvalRequestStatus) {
                $submittedAt = $documentDate->copy()->setTime(9, 0);
                $completedAt = $approvalRequestStatus === 'pending'
                    ? null
                    : $documentDate->copy()->addDay()->setTime(15, 0);

                $this->syncApprovalRequest(
                    PurchaseRequest::class,
                    $purchaseRequest->id,
                    'purchase_request_default',
                    $approvalRequestStatus,
                    $documentRequesterId,
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
        array $pipelineConfig,
        int $documentRequesterId
    ): array {
        $year = now()->format('Y');

        foreach (PurchaseOrder::query()->where('po_number', 'like', "PO-{$year}-99%")->get() as $legacy) {
            $target = "PO-{$year}-0001" . substr($legacy->po_number, -2);

            if (PurchaseOrder::query()->where('po_number', $target)->exists()) {
                $legacy->delete();
            } else {
                $legacy->update(['po_number' => $target]);
            }
        }

        $definitions = [
            ['number' => "PO-{$year}-000101", 'status' => 'draft', 'source_status' => 'approved'],
            ['number' => "PO-{$year}-000102", 'status' => 'pending_approval', 'source_status' => 'approved'],
            ['number' => "PO-{$year}-000103", 'status' => 'confirmed', 'source_status' => 'approved'],
            ['number' => "PO-{$year}-000104", 'status' => 'rejected', 'source_status' => 'approved'],
            ['number' => "PO-{$year}-000105", 'status' => 'partially_received', 'source_status' => 'partially_ordered'],
            ['number' => "PO-{$year}-000106", 'status' => 'fully_received', 'source_status' => 'fully_ordered'],
            ['number' => "PO-{$year}-000107", 'status' => 'cancelled', 'source_status' => 'approved'],
            ['number' => "PO-{$year}-000108", 'status' => 'partially_received', 'source_status' => 'fully_ordered'],
        ];

        $histories = [
            'draft' => [],
            'pending_approval' => [['draft', 'pending_approval']],
            'confirmed' => [['draft', 'confirmed']],
            'rejected' => [['draft', 'pending_approval'], ['pending_approval', 'rejected']],
            'partially_received' => [['draft', 'confirmed'], ['confirmed', 'partially_received']],
            'fully_received' => [
                ['draft', 'confirmed'],
                ['confirmed', 'partially_received'],
                ['partially_received', 'fully_received'],
            ],
            'cancelled' => [['draft', 'confirmed'], ['confirmed', 'cancelled']],
        ];

        $purchaseOrders = [];
        $purchaseOrderItems = [];

        foreach ($definitions as $index => $definition) {
            $status = $definition['status'];
            $sourceItems = $purchaseRequestItems[$definition['source_status']] ?? [];

            $firstProduct = $products[($index + 2) % $products->count()];
            $secondProduct = $products[($index + 3) % $products->count()];

            // Kuantitas bervariasi agar data demo tidak seragam (W5-23).
            $firstQuantity = (float) (6 + (($index * 5) % 17));
            $secondQuantity = (float) (3 + (($index * 3) % 11));

            $firstUnitPrice = (float) ($firstProduct->cost ?? 12000);
            $secondUnitPrice = (float) ($secondProduct->cost ?? 14000);

            $firstReceived = 0.00;
            $secondReceived = 0.00;

            $firstLineTotal = $firstQuantity * $firstUnitPrice;
            $secondLineTotal = $secondQuantity * $secondUnitPrice;
            $subtotal = $firstLineTotal + $secondLineTotal;
            $taxAmount = round($subtotal * 0.11, 2);
            $grandTotal = $subtotal + $taxAmount;

            $approved = in_array($status, ['confirmed', 'partially_received', 'fully_received', 'closed'], true);

            // Rotasi supplier aktif agar tidak semua PO ke vendor yang sama. Produk
            // layanan SaaS hanya dipesan dari supplier kategori Jasa TI (W7-17).
            $poSuppliers = Supplier::query()->orderBy('id')->get();
            $isSoftwareOrder = $firstProduct->code === 'SW-CLD-001' || $secondProduct->code === 'SW-CLD-001';
            $itServicesCategory = DemoMasterData::SUPPLIER_CATEGORY_IT_SERVICES;
            $eligibleSuppliers = $isSoftwareOrder
                ? $poSuppliers->filter(fn (Supplier $candidate) => $candidate->category?->name === $itServicesCategory)
                : $poSuppliers->reject(fn (Supplier $candidate) => $candidate->category?->name === $itServicesCategory);
            $eligibleSuppliers = $eligibleSuppliers->isEmpty() ? $poSuppliers : $eligibleSuppliers->values();
            $poSupplierId = $eligibleSuppliers->isEmpty()
                ? $supplier->id
                : $eligibleSuppliers[$index % $eligibleSuppliers->count()]->id;

            $purchaseOrder = PurchaseOrder::updateOrCreate(
                ['po_number' => $definition['number']],
                [
                    'supplier_id' => $poSupplierId,
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
                    'notes' => 'Pesanan pembelian sesuai permintaan',
                    'shipping_address' => 'Gudang Utama',
                    'approved_by' => $approved ? $adminUserId : null,
                    'approved_at' => $approved ? now()->subDays(20 - $index) : null,
                    'created_by' => $adminUserId,
                ]
            );

            $firstSourceItem = $sourceItems[0] ?? null;
            $secondSourceItem = $sourceItems[1] ?? null;

            // Rotasi produk per PO bisa berubah saat seed ulang; buang baris lama
            // agar kuantitas PO tidak menggandakan (legacy cleanup, idempotent).
            $this->purgeStalePurchaseOrderItems(
                $purchaseOrder->id,
                [$firstProduct->id, $secondProduct->id],
            );

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
                    'notes' => 'Kebutuhan operasional gudang',
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
                    'notes' => 'Kebutuhan perawatan fasilitas',
                ]
            );

            $purchaseOrders[$definition['number']] = $purchaseOrder;
            $purchaseOrderItems[$definition['number']] = [$firstItem, $secondItem];

            $documentDate = Carbon::parse($purchaseOrder->order_date);

            $this->syncEntityPipelineState(
                $pipelineConfig,
                'App\\Models\\PurchaseOrder',
                $purchaseOrder->id,
                $status,
                $histories[$status] ?? [],
                $adminUserId,
                $documentDate,
                $documentRequesterId
            );

            $approvalRequestStatus = match ($status) {
                'pending_approval' => 'pending',
                'rejected' => 'rejected',
                default => null,
            };

            if ($approvalRequestStatus) {
                $submittedAt = $documentDate->copy()->setTime(10, 0);
                $completedAt = $approvalRequestStatus === 'pending'
                    ? null
                    : $documentDate->copy()->addDay()->setTime(16, 0);

                $this->syncApprovalRequest(
                    PurchaseOrder::class,
                    $purchaseOrder->id,
                    'purchase_order_default',
                    $approvalRequestStatus,
                    $documentRequesterId,
                    $submittedAt,
                    $completedAt,
                );
            }
        }

        return [$purchaseOrders, $purchaseOrderItems];
    }

    /**
     * Hapus baris item PO demo yang produknya sudah tidak dipakai. Dependen
     * (retur dan penerimaan) dibersihkan dulu; seeder GR/retur membuatnya ulang.
     *
     * @param  array<int, int>  $keepProductIds
     */
    private function purgeStalePurchaseOrderItems(int $purchaseOrderId, array $keepProductIds): void
    {
        $staleItemIds = PurchaseOrderItem::query()
            ->where('purchase_order_id', $purchaseOrderId)
            ->whereNotIn('product_id', $keepProductIds)
            ->pluck('id');

        if ($staleItemIds->isEmpty()) {
            return;
        }

        $receiptItemIds = GoodsReceiptItem::query()
            ->whereIn('purchase_order_item_id', $staleItemIds)
            ->pluck('id');

        SupplierReturnItem::query()
            ->whereIn('goods_receipt_item_id', $receiptItemIds)
            ->delete();

        GoodsReceiptItem::query()->whereIn('id', $receiptItemIds)->delete();

        PurchaseOrderItem::query()->whereIn('id', $staleItemIds)->delete();
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
                'created_at' => $submittedAt,
                'metadata' => [
                    'source' => 'PurchasingSampleDataSeeder',
                    'flow_code' => $approvalFlowCode,
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
                        'created_at' => $actedAt ?? $submittedAt,
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
                    'created_at' => $actedAt ?? $submittedAt,
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
                    'created_at' => $completedAt ?? $submittedAt,
                    'metadata' => [
                        'source' => 'PurchasingSampleDataSeeder',
                        'flow_code' => $approvalFlowCode,
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

        foreach (GoodsReceipt::query()->where('gr_number', 'like', "GR-{$year}-99%")->get() as $legacy) {
            $target = "GR-{$year}-0001" . substr($legacy->gr_number, -2);

            if (GoodsReceipt::query()->where('gr_number', $target)->exists()) {
                $legacy->delete();
            } else {
                $legacy->update(['gr_number' => $target]);
            }
        }

        // Goods receipt lines are the single source of truth for received
        // quantity. `received => null` means "receive the full ordered line".
        $definitions = [
            [
                'number' => "GR-{$year}-000101",
                'status' => 'draft',
                'po_number' => "PO-{$year}-000103",
                'items' => [
                    ['line' => 0, 'received' => 2.00, 'accepted' => 2.00],
                ],
            ],
            [
                'number' => "GR-{$year}-000102",
                'status' => 'confirmed',
                'po_number' => "PO-{$year}-000105",
                'items' => [
                    ['line' => 0, 'received' => 4.00, 'accepted' => 3.00, 'rejected' => 1.00],
                ],
            ],
            [
                'number' => "GR-{$year}-000103",
                'status' => 'confirmed',
                'po_number' => "PO-{$year}-000106",
                'items' => [
                    ['line' => 0, 'received' => null, 'accepted' => null],
                    ['line' => 1, 'received' => null, 'accepted' => null],
                ],
            ],
            [
                'number' => "GR-{$year}-000104",
                'status' => 'cancelled',
                'po_number' => "PO-{$year}-000107",
                'items' => [
                    ['line' => 0, 'received' => 0.00, 'accepted' => 0.00, 'rejected' => 0.00],
                ],
            ],
            [
                'number' => "GR-{$year}-000105",
                'status' => 'confirmed',
                'po_number' => "PO-{$year}-000108",
                'items' => [
                    ['line' => 0, 'received' => null, 'accepted' => null],
                ],
            ],
        ];

        $goodsReceipts = [];
        $goodsReceiptItems = [];

        foreach ($definitions as $index => $definition) {
            $purchaseOrder = $purchaseOrders[$definition['po_number']] ?? null;
            $poItems = $purchaseOrderItems[$definition['po_number']] ?? [];

            if (! $purchaseOrder) {
                continue;
            }

            $confirmed = $definition['status'] === 'confirmed';

            $goodsReceipt = GoodsReceipt::updateOrCreate(
                ['gr_number' => $definition['number']],
                [
                    'purchase_order_id' => $purchaseOrder->id,
                    'warehouse_id' => $warehouse->id,
                    'receipt_date' => now()->subDays(12 - $index)->toDateString(),
                    'supplier_delivery_note' => 'SJ-' . $year . '-0001' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'status' => $definition['status'],
                    'notes' => 'Penerimaan barang sesuai pesanan pembelian',
                    'received_by' => $requesterId,
                    'confirmed_by' => $confirmed ? $adminUserId : null,
                    'confirmed_at' => $confirmed ? now()->subDays(10 - $index) : null,
                    'created_by' => $adminUserId,
                ]
            );

            // Recreate lines so re-seeding never leaves stale receipt rows.
            // Supplier return items reference receipt lines, so clear those
            // dependents first to keep the seeder idempotent.
            $existingItemIds = $goodsReceipt->items()->pluck('id');
            if ($existingItemIds->isNotEmpty()) {
                SupplierReturnItem::query()
                    ->whereIn('goods_receipt_item_id', $existingItemIds)
                    ->delete();
            }
            $goodsReceipt->items()->delete();

            foreach ($definition['items'] as $itemDefinition) {
                $poItem = $poItems[$itemDefinition['line']] ?? null;

                if (! $poItem) {
                    continue;
                }

                $orderedQuantity = (float) $poItem->quantity;
                $received = $itemDefinition['received'] === null
                    ? $orderedQuantity
                    : min((float) $itemDefinition['received'], $orderedQuantity);
                $accepted = $itemDefinition['accepted'] === null
                    ? $received
                    : min((float) $itemDefinition['accepted'], $received);
                $rejected = $itemDefinition['rejected'] ?? max(0.0, $received - $accepted);

                $goodsReceiptItem = GoodsReceiptItem::updateOrCreate(
                    [
                        'goods_receipt_id' => $goodsReceipt->id,
                        'purchase_order_item_id' => $poItem->id,
                    ],
                    [
                        'product_id' => $poItem->product_id,
                        'unit_id' => $poItem->unit_id ?? $fallbackUnitId,
                        'quantity_received' => $received,
                        'quantity_accepted' => $accepted,
                        'quantity_rejected' => $rejected,
                        'unit_price' => $poItem->unit_price,
                        'notes' => 'Item penerimaan barang',
                    ]
                );

                $goodsReceiptItems[$definition['status'] . '_' . ($index + 1) . '_' . $itemDefinition['line']] = $goodsReceiptItem;
            }

            $goodsReceipts[$definition['status'] . '_' . ($index + 1)] = $goodsReceipt;
        }

        return [$goodsReceipts, $goodsReceiptItems];
    }

    /**
     * Derive each purchase order line's received quantity from the confirmed
     * goods receipt lines, keeping the stored column and every report aligned.
     */
    private function syncPurchaseOrderReceivedQuantities(): void
    {
        $receivedByItem = DB::table('goods_receipt_items')
            ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_items.goods_receipt_id')
            ->where('goods_receipts.status', 'confirmed')
            ->groupBy('goods_receipt_items.purchase_order_item_id')
            ->selectRaw('goods_receipt_items.purchase_order_item_id as purchase_order_item_id, SUM(goods_receipt_items.quantity_received) as received')
            ->pluck('received', 'purchase_order_item_id');

        foreach (PurchaseOrderItem::query()->get() as $item) {
            $item->update([
                'quantity_received' => (float) ($receivedByItem[$item->id] ?? 0),
            ]);
        }
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

        foreach (SupplierReturn::query()->where('return_number', 'like', "SR-{$year}-99%")->get() as $legacy) {
            $target = "SR-{$year}-0001" . substr($legacy->return_number, -2);

            if (SupplierReturn::query()->where('return_number', $target)->exists()) {
                $legacy->delete();
            } else {
                $legacy->update(['return_number' => $target]);
            }
        }

        // Retur tersebar ke beberapa PO/GR, bukan menumpuk di satu dokumen
        // (W6-12). Hanya GR terkonfirmasi yang boleh dirujuk retur (W7-03).
        $candidates = [];

        foreach ($goodsReceipts as $receipt) {
            if ($receipt->status !== 'confirmed') {
                continue;
            }

            $item = $receipt->items()->orderBy('id')->first();
            $order = PurchaseOrder::query()->find($receipt->purchase_order_id);

            if ($item && $order) {
                $candidates[] = [$order, $receipt, $item];
            }
        }

        if ($candidates === []) {
            return;
        }

        $definitions = [
            ['number' => "SR-{$year}-000101", 'status' => 'draft', 'reason' => 'damaged', 'quantity' => 1.00],
            ['number' => "SR-{$year}-000102", 'status' => 'confirmed', 'reason' => 'defective', 'quantity' => 2.00],
            ['number' => "SR-{$year}-000103", 'status' => 'cancelled', 'reason' => 'other', 'quantity' => 1.00],
        ];

        foreach ($definitions as $index => $definition) {
            [$baseOrder, $baseReceipt, $baseReceiptItem] = $candidates[$index % count($candidates)];

            $supplierReturn = SupplierReturn::updateOrCreate(
                ['return_number' => $definition['number']],
                [
                    'purchase_order_id' => $baseOrder->id,
                    'goods_receipt_id' => $baseReceipt->id,
                    'supplier_id' => $baseOrder->supplier_id ?: $supplier->id,
                    'warehouse_id' => $warehouse->id,
                    'return_date' => now()->subDays(7 - $index)->toDateString(),
                    'reason' => $definition['reason'],
                    'status' => $definition['status'],
                    'notes' => 'Retur barang ke supplier karena tidak sesuai spesifikasi',
                    'created_by' => $adminUserId,
                ]
            );

            $supplierReturn->items()->delete();

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
                    'notes' => 'Item retur ke supplier',
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
        int $adminUserId,
        Carbon $documentDate,
        int $performedBy
    ): void {
        $pipeline = $pipelineConfig['pipeline'];
        $states = $pipelineConfig['states'];
        $transitions = $pipelineConfig['transitions'];

        $initialState = $states['draft'] ?? null;
        $targetState = $states[$targetStateCode] ?? null;

        if (! $initialState || ! $targetState) {
            return;
        }

        PipelineStateLog::query()
            ->where('pipeline_entity_state_id', PipelineEntityState::query()
                ->where('pipeline_id', $pipeline->id)
                ->where('entity_type', $entityType)
                ->where('entity_id', $entityId)
                ->value('id') ?? 0)
            ->delete();

        // Timestamp mengikuti tanggal dokumen: transisi pertama di hari dokumen,
        // lalu tiap transisi berikutnya berselang satu jam, tanpa now().
        $cursor = $documentDate->copy()->startOfDay()->setTime(8, 0);
        $logs = [];

        $logs[] = [
            'from_state_id' => null,
            'to_state_id' => $initialState->id,
            'transition_id' => null,
            'comment' => 'Initial pipeline assignment',
            'created_at' => $cursor->copy(),
        ];

        foreach ($history as $transitionStep) {
            [$fromCode, $toCode] = $transitionStep;

            $fromState = $states[$fromCode] ?? null;
            $toState = $states[$toCode] ?? null;
            $transition = $transitions[$fromCode . '->' . $toCode] ?? null;

            if (! $fromState || ! $toState) {
                continue;
            }

            $cursor = $cursor->copy()->addHour();
            $logs[] = [
                'from_state_id' => $fromState->id,
                'to_state_id' => $toState->id,
                'transition_id' => $transition?->id,
                'comment' => null,
                'created_at' => $cursor->copy(),
            ];
        }

        $entityState = PipelineEntityState::updateOrCreate(
            [
                'pipeline_id' => $pipeline->id,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ],
            [
                'current_state_id' => $targetState->id,
                'last_transitioned_by' => $performedBy,
                'last_transitioned_at' => $cursor->copy(),
                'metadata' => null,
            ]
        );

        foreach ($logs as $log) {
            PipelineStateLog::create(array_merge($log, [
                'pipeline_entity_state_id' => $entityState->id,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'performed_by' => $performedBy,
            ]));
        }
    }
}
