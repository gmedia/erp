<?php

namespace Database\Seeders;

use App\Models\Pipeline;
use App\Models\PipelineState;
use App\Models\PipelineTransition;
use App\Models\PipelineTransitionAction;
use App\Models\User;
use Illuminate\Database\Seeder;

class PipelineSampleDataSeeder extends Seeder
{
    /**
     * Seed the Asset Lifecycle pipeline with realistic configuration.
     *
     * Creates:
     * - 1 Pipeline: "Asset Lifecycle"
     * - 6 States: Draft, Active, In Maintenance, Disposed, Lost, Cancelled
     * - 6 Transitions with appropriate permissions and confirmation settings
     * - 6+ Transition Actions (update_field to sync assets.status)
     */
    public function run(): void
    {
        $adminUserId = User::query()->where('email', config('app.admin'))->value('id');

        // ── Pipeline ────────────────────────────────────────────────
        $pipeline = Pipeline::firstOrCreate(
            ['code' => 'asset_lifecycle'],
            [
                'name' => 'Asset Lifecycle',
                'code' => 'asset_lifecycle',
                'entity_type' => 'App\Models\Asset',
                'description' => 'Mengelola siklus hidup aset perusahaan — dari draft, aktif, maintenance, '
                    . 'hingga disposed atau lost.',
                'version' => 1,
                'is_active' => true,
                'conditions' => null,
                'created_by' => $adminUserId,
            ]
        );

        // ── States ──────────────────────────────────────────────────
        $statesData = [
            [
                'code' => 'draft',
                'name' => 'Draft',
                'type' => 'initial',
                'color' => '#6B7280',
                'icon' => 'FileEdit',
                'description' => 'Aset baru dibuat, belum aktif digunakan.',
                'sort_order' => 0,
            ],
            [
                'code' => 'active',
                'name' => 'Active',
                'type' => 'intermediate',
                'color' => '#10B981',
                'icon' => 'CircleCheck',
                'description' => 'Aset aktif dan sedang digunakan.',
                'sort_order' => 10,
            ],
            [
                'code' => 'maintenance',
                'name' => 'In Maintenance',
                'type' => 'intermediate',
                'color' => '#F59E0B',
                'icon' => 'Wrench',
                'description' => 'Aset sedang dalam perbaikan atau perawatan.',
                'sort_order' => 20,
            ],
            [
                'code' => 'disposed',
                'name' => 'Disposed',
                'type' => 'final',
                'color' => '#EF4444',
                'icon' => 'Trash2',
                'description' => 'Aset telah dilepas atau dihapusbukukan.',
                'sort_order' => 30,
            ],
            [
                'code' => 'lost',
                'name' => 'Lost',
                'type' => 'final',
                'color' => '#DC2626',
                'icon' => 'AlertTriangle',
                'description' => 'Aset hilang dan tidak dapat ditemukan.',
                'sort_order' => 40,
            ],
            [
                'code' => 'cancelled',
                'name' => 'Cancelled',
                'type' => 'final',
                'color' => '#9CA3AF',
                'icon' => 'XCircle',
                'description' => 'Pendaftaran aset dibatalkan sebelum diaktifkan.',
                'sort_order' => 50,
            ],
        ];

        $states = [];
        foreach ($statesData as $stateData) {
            $states[$stateData['code']] = PipelineState::firstOrCreate(
                ['pipeline_id' => $pipeline->id, 'code' => $stateData['code']],
                array_merge($stateData, ['pipeline_id' => $pipeline->id])
            );
        }

        // ── Transitions ─────────────────────────────────────────────
        $transitionsData = [
            [
                'from' => 'draft',
                'to' => 'active',
                'name' => 'Activate',
                'code' => 'activate',
                'description' => 'Mengaktifkan aset untuk mulai digunakan.',
                'required_permission' => 'asset.edit',
                'guard_conditions' => null,
                'requires_confirmation' => false,
                'requires_comment' => false,
                'requires_approval' => true,
                'sort_order' => 10,
                'actions' => [
                    ['action_type' => 'trigger_approval', 'execution_order' => 5, 'config' => []],
                    [
                        'action_type' => 'update_field',
                        'execution_order' => 10,
                        'config' => ['field' => 'status', 'value' => 'active'],
                    ],
                ],
            ],
            [
                'from' => 'draft',
                'to' => 'cancelled',
                'name' => 'Cancel',
                'code' => 'cancel',
                'description' => 'Membatalkan pendaftaran aset yang belum aktif.',
                'required_permission' => 'asset.edit',
                'guard_conditions' => null,
                'requires_confirmation' => true,
                'requires_comment' => false,
                'requires_approval' => false,
                'sort_order' => 20,
                'actions' => [
                    [
                        'action_type' => 'update_field',
                        'execution_order' => 10,
                        'config' => ['field' => 'status', 'value' => 'draft'],
                    ],
                ],
            ],
            [
                'from' => 'active',
                'to' => 'maintenance',
                'name' => 'Send to Maintenance',
                'code' => 'send_maintenance',
                'description' => 'Mengirim aset untuk perbaikan atau perawatan.',
                'required_permission' => 'asset.edit',
                'guard_conditions' => null,
                'requires_confirmation' => false,
                'requires_comment' => false,
                'requires_approval' => false,
                'sort_order' => 10,
                'actions' => [
                    [
                        'action_type' => 'update_field',
                        'execution_order' => 10,
                        'config' => ['field' => 'status', 'value' => 'maintenance'],
                    ],
                ],
            ],
            [
                'from' => 'maintenance',
                'to' => 'active',
                'name' => 'Return from Maintenance',
                'code' => 'return_maintenance',
                'description' => 'Mengembalikan aset ke status aktif setelah perbaikan selesai.',
                'required_permission' => 'asset.edit',
                'guard_conditions' => null,
                'requires_confirmation' => false,
                'requires_comment' => false,
                'requires_approval' => false,
                'sort_order' => 10,
                'actions' => [
                    [
                        'action_type' => 'update_field',
                        'execution_order' => 10,
                        'config' => ['field' => 'status', 'value' => 'active'],
                    ],
                ],
            ],
            [
                'from' => 'active',
                'to' => 'disposed',
                'name' => 'Dispose',
                'code' => 'dispose',
                'description' => 'Melepas atau menghapusbukukan aset. Memerlukan konfirmasi dan alasan.',
                'required_permission' => 'asset.edit',
                'guard_conditions' => null,
                'requires_confirmation' => true,
                'requires_comment' => true,
                'requires_approval' => false,
                'sort_order' => 20,
                'actions' => [
                    [
                        'action_type' => 'update_field',
                        'execution_order' => 10,
                        'config' => ['field' => 'status', 'value' => 'disposed'],
                    ],
                ],
            ],
            [
                'from' => 'active',
                'to' => 'lost',
                'name' => 'Mark as Lost',
                'code' => 'mark_lost',
                'description' => 'Menandai aset sebagai hilang. Memerlukan konfirmasi dan keterangan.',
                'required_permission' => 'asset.edit',
                'guard_conditions' => null,
                'requires_confirmation' => true,
                'requires_comment' => true,
                'requires_approval' => false,
                'sort_order' => 30,
                'actions' => [
                    [
                        'action_type' => 'update_field',
                        'execution_order' => 10,
                        'config' => ['field' => 'status', 'value' => 'lost'],
                    ],
                ],
            ],
        ];

        foreach ($transitionsData as $tData) {
            $fromState = $states[$tData['from']];
            $toState = $states[$tData['to']];
            $actions = $tData['actions'];

            unset($tData['from'], $tData['to'], $tData['actions']);

            $transition = PipelineTransition::firstOrCreate(
                [
                    'pipeline_id' => $pipeline->id,
                    'from_state_id' => $fromState->id,
                    'to_state_id' => $toState->id,
                ],
                array_merge($tData, [
                    'pipeline_id' => $pipeline->id,
                    'from_state_id' => $fromState->id,
                    'to_state_id' => $toState->id,
                    'is_active' => true,
                ])
            );

            // ── Transition Actions ──────────────────────────────────
            foreach ($actions as $actionData) {
                PipelineTransitionAction::firstOrCreate(
                    [
                        'pipeline_transition_id' => $transition->id,
                        'execution_order' => $actionData['execution_order'],
                    ],
                    array_merge($actionData, [
                        'pipeline_transition_id' => $transition->id,
                        'is_async' => false,
                        'on_failure' => 'abort',
                        'is_active' => true,
                    ])
                );
            }
        }

        $this->seedSupplierBillLifecycle($adminUserId);
        $this->seedApPaymentLifecycle($adminUserId);
    }

    private function seedSupplierBillLifecycle(?int $adminUserId): void
    {
        $pipeline = Pipeline::firstOrCreate(
            ['code' => 'supplier_bill_lifecycle'],
            [
                'name' => 'Supplier Bill Lifecycle',
                'code' => 'supplier_bill_lifecycle',
                'entity_type' => 'App\Models\SupplierBill',
                'description' => 'Mengelola siklus hidup tagihan supplier — dari draft, confirmed, pembayaran, hingga lunas atau void.',
                'version' => 1,
                'is_active' => true,
                'conditions' => null,
                'created_by' => $adminUserId,
            ]
        );

        $statesData = [
            ['code' => 'draft', 'name' => 'Draft', 'type' => 'initial', 'color' => '#6B7280', 'icon' => 'FileEdit', 'description' => 'Bill baru dibuat, belum dikonfirmasi.', 'sort_order' => 0],
            ['code' => 'confirmed', 'name' => 'Confirmed', 'type' => 'intermediate', 'color' => '#10B981', 'icon' => 'CircleCheck', 'description' => 'Bill dikonfirmasi, jurnal AP dibuat.', 'sort_order' => 10],
            ['code' => 'partially_paid', 'name' => 'Partially Paid', 'type' => 'intermediate', 'color' => '#3B82F6', 'icon' => 'CreditCard', 'description' => 'Sebagian tagihan sudah dibayar.', 'sort_order' => 20],
            ['code' => 'paid', 'name' => 'Paid', 'type' => 'final', 'color' => '#059669', 'icon' => 'CircleCheckBig', 'description' => 'Tagihan sudah lunas.', 'sort_order' => 30],
            ['code' => 'overdue', 'name' => 'Overdue', 'type' => 'intermediate', 'color' => '#EF4444', 'icon' => 'AlertTriangle', 'description' => 'Tagihan melewati jatuh tempo.', 'sort_order' => 40],
            ['code' => 'cancelled', 'name' => 'Cancelled', 'type' => 'final', 'color' => '#9CA3AF', 'icon' => 'Ban', 'description' => 'Bill dibatalkan sebelum confirmed.', 'sort_order' => 50],
            ['code' => 'void', 'name' => 'Void', 'type' => 'final', 'color' => '#DC2626', 'icon' => 'XCircle', 'description' => 'Bill yang sudah confirmed dibatalkan (jurnal reversal).', 'sort_order' => 60],
        ];

        $states = [];
        foreach ($statesData as $stateData) {
            $states[$stateData['code']] = PipelineState::firstOrCreate(
                ['pipeline_id' => $pipeline->id, 'code' => $stateData['code']],
                array_merge($stateData, ['pipeline_id' => $pipeline->id, 'metadata' => null])
            );
        }

        $transitions = [
            ['from' => 'draft', 'to' => 'confirmed', 'name' => 'Confirm Bill', 'code' => 'confirm_bill', 'sort_order' => 0],
            ['from' => 'confirmed', 'to' => 'partially_paid', 'name' => 'Record Partial Payment', 'code' => 'partial_payment', 'sort_order' => 10],
            ['from' => 'confirmed', 'to' => 'paid', 'name' => 'Record Full Payment', 'code' => 'full_payment', 'sort_order' => 20],
            ['from' => 'confirmed', 'to' => 'overdue', 'name' => 'Mark Overdue', 'code' => 'mark_overdue', 'sort_order' => 30],
            ['from' => 'partially_paid', 'to' => 'paid', 'name' => 'Record Remaining Payment', 'code' => 'remaining_payment', 'sort_order' => 40],
            ['from' => 'partially_paid', 'to' => 'overdue', 'name' => 'Mark Overdue', 'code' => 'mark_overdue_partial', 'sort_order' => 50],
            ['from' => 'overdue', 'to' => 'partially_paid', 'name' => 'Record Payment', 'code' => 'overdue_partial_payment', 'sort_order' => 60],
            ['from' => 'overdue', 'to' => 'paid', 'name' => 'Record Full Payment', 'code' => 'overdue_full_payment', 'sort_order' => 70],
            ['from' => 'draft', 'to' => 'cancelled', 'name' => 'Cancel Bill', 'code' => 'cancel_bill', 'sort_order' => 80],
            ['from' => 'confirmed', 'to' => 'void', 'name' => 'Void Bill', 'code' => 'void_bill', 'sort_order' => 90, 'requires_confirmation' => true, 'requires_comment' => true],
        ];

        foreach ($transitions as $t) {
            PipelineTransition::firstOrCreate(
                ['pipeline_id' => $pipeline->id, 'code' => $t['code']],
                [
                    'pipeline_id' => $pipeline->id,
                    'from_state_id' => $states[$t['from']]->id,
                    'to_state_id' => $states[$t['to']]->id,
                    'name' => $t['name'],
                    'code' => $t['code'],
                    'sort_order' => $t['sort_order'],
                    'requires_confirmation' => $t['requires_confirmation'] ?? false,
                    'requires_comment' => $t['requires_comment'] ?? false,
                    'requires_approval' => false,
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedApPaymentLifecycle(?int $adminUserId): void
    {
        $pipeline = Pipeline::firstOrCreate(
            ['code' => 'ap_payment_lifecycle'],
            [
                'name' => 'AP Payment Lifecycle',
                'code' => 'ap_payment_lifecycle',
                'entity_type' => 'App\Models\ApPayment',
                'description' => 'Mengelola siklus hidup pembayaran hutang — dari draft, approval, confirmed, hingga reconciled atau void.',
                'version' => 1,
                'is_active' => true,
                'conditions' => null,
                'created_by' => $adminUserId,
            ]
        );

        $statesData = [
            ['code' => 'draft', 'name' => 'Draft', 'type' => 'initial', 'color' => '#6B7280', 'icon' => 'FileEdit', 'description' => 'Payment baru dibuat.', 'sort_order' => 0],
            ['code' => 'pending_approval', 'name' => 'Pending Approval', 'type' => 'intermediate', 'color' => '#F59E0B', 'icon' => 'Clock', 'description' => 'Menunggu persetujuan.', 'sort_order' => 10],
            ['code' => 'confirmed', 'name' => 'Confirmed', 'type' => 'intermediate', 'color' => '#10B981', 'icon' => 'CircleCheck', 'description' => 'Payment dikonfirmasi, jurnal dibuat.', 'sort_order' => 20],
            ['code' => 'reconciled', 'name' => 'Reconciled', 'type' => 'final', 'color' => '#059669', 'icon' => 'CircleCheckBig', 'description' => 'Payment sudah dicocokkan dengan mutasi bank.', 'sort_order' => 30],
            ['code' => 'cancelled', 'name' => 'Cancelled', 'type' => 'final', 'color' => '#9CA3AF', 'icon' => 'Ban', 'description' => 'Payment dibatalkan.', 'sort_order' => 40],
            ['code' => 'void', 'name' => 'Void', 'type' => 'final', 'color' => '#DC2626', 'icon' => 'XCircle', 'description' => 'Payment yang sudah confirmed dibatalkan (jurnal reversal).', 'sort_order' => 50],
        ];

        $states = [];
        foreach ($statesData as $stateData) {
            $states[$stateData['code']] = PipelineState::firstOrCreate(
                ['pipeline_id' => $pipeline->id, 'code' => $stateData['code']],
                array_merge($stateData, ['pipeline_id' => $pipeline->id, 'metadata' => null])
            );
        }

        $transitions = [
            ['from' => 'draft', 'to' => 'pending_approval', 'name' => 'Submit for Approval', 'code' => 'submit_approval', 'sort_order' => 0],
            ['from' => 'draft', 'to' => 'confirmed', 'name' => 'Confirm Payment', 'code' => 'confirm_payment', 'sort_order' => 10],
            ['from' => 'pending_approval', 'to' => 'confirmed', 'name' => 'Approve Payment', 'code' => 'approve_payment', 'sort_order' => 20],
            ['from' => 'pending_approval', 'to' => 'cancelled', 'name' => 'Reject Payment', 'code' => 'reject_payment', 'sort_order' => 30],
            ['from' => 'confirmed', 'to' => 'reconciled', 'name' => 'Mark Reconciled', 'code' => 'mark_reconciled', 'sort_order' => 40],
            ['from' => 'draft', 'to' => 'cancelled', 'name' => 'Cancel Payment', 'code' => 'cancel_payment', 'sort_order' => 50],
            ['from' => 'confirmed', 'to' => 'void', 'name' => 'Void Payment', 'code' => 'void_payment', 'sort_order' => 60, 'requires_confirmation' => true, 'requires_comment' => true],
        ];

        foreach ($transitions as $t) {
            PipelineTransition::firstOrCreate(
                ['pipeline_id' => $pipeline->id, 'code' => $t['code']],
                [
                    'pipeline_id' => $pipeline->id,
                    'from_state_id' => $states[$t['from']]->id,
                    'to_state_id' => $states[$t['to']]->id,
                    'name' => $t['name'],
                    'code' => $t['code'],
                    'sort_order' => $t['sort_order'],
                    'requires_confirmation' => $t['requires_confirmation'] ?? false,
                    'requires_comment' => $t['requires_comment'] ?? false,
                    'requires_approval' => false,
                    'is_active' => true,
                ]
            );
        }
    }
}
