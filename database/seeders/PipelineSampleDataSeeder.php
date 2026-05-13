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
    public function run(): void
    {
        $adminUserId = User::first()?->id ?? 1;

        // ── Supplier Bill Lifecycle Pipeline ─────────────────────────
        $this->seedSupplierBillLifecycle($adminUserId);

        // ── AP Payment Lifecycle Pipeline ──────────────────────────
        $this->seedApPaymentLifecycle($adminUserId);

        // ── Customer Invoice Lifecycle Pipeline ───────────────────────
        $this->seedCustomerInvoiceLifecyclePipeline($adminUserId);

        // ── Asset Lifecycle Pipeline ─────────────────────────────────
        $this->seedAssetLifecycle($adminUserId);
    }

    /**
     * Seed the Supplier Bill Lifecycle pipeline.
     */
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

    /**
     * Seed the AP Payment Lifecycle pipeline.
     */
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

    /**
     * Seed the Customer Invoice Lifecycle pipeline.
     */
    private function seedCustomerInvoiceLifecyclePipeline(int $adminUserId): void
    {
        $pipeline = Pipeline::firstOrCreate(
            ['code' => 'customer_invoice_lifecycle'],
            [
                'name' => 'Customer Invoice Lifecycle',
                'code' => 'customer_invoice_lifecycle',
                'entity_type' => 'App\Models\CustomerInvoice',
                'description' => 'Mengelola siklus hidup invoice pelanggan — dari draft, sent, paid, hingga cancelled atau void.',
                'version' => 1,
                'is_active' => true,
                'conditions' => null,
                'created_by' => $adminUserId,
            ]
        );

        $statesData = [
            ['code' => 'draft', 'name' => 'Draft', 'type' => 'initial', 'color' => '#6B7280', 'icon' => 'FileEdit', 'description' => 'Invoice baru dibuat, belum dikirim ke pelanggan.', 'sort_order' => 0],
            ['code' => 'sent', 'name' => 'Sent', 'type' => 'intermediate', 'color' => '#10B981', 'icon' => 'Send', 'description' => 'Invoice telah dikirim ke pelanggan.', 'sort_order' => 10],
            ['code' => 'partially_paid', 'name' => 'Partially Paid', 'type' => 'intermediate', 'color' => '#3B82F6', 'icon' => 'CircleDollarSign', 'description' => 'Invoice telah dibayar sebagian.', 'sort_order' => 20],
            ['code' => 'paid', 'name' => 'Paid', 'type' => 'final', 'color' => '#059669', 'icon' => 'CircleCheck', 'description' => 'Invoice telah dibayar lunas.', 'sort_order' => 30],
            ['code' => 'overdue', 'name' => 'Overdue', 'type' => 'intermediate', 'color' => '#EF4444', 'icon' => 'AlertTriangle', 'description' => 'Invoice telah melewati tanggal jatuh tempo.', 'sort_order' => 40],
            ['code' => 'cancelled', 'name' => 'Cancelled', 'type' => 'final', 'color' => '#9CA3AF', 'icon' => 'XCircle', 'description' => 'Invoice dibatalkan sebelum dikirim.', 'sort_order' => 50],
            ['code' => 'void', 'name' => 'Void', 'type' => 'final', 'color' => '#DC2626', 'icon' => 'Ban', 'description' => 'Invoice dibatalkan setelah dikirim.', 'sort_order' => 60],
        ];

        $states = [];
        foreach ($statesData as $stateData) {
            $states[$stateData['code']] = PipelineState::firstOrCreate(
                ['pipeline_id' => $pipeline->id, 'code' => $stateData['code']],
                array_merge($stateData, ['pipeline_id' => $pipeline->id, 'metadata' => null])
            );
        }

        $transitionsData = [
            ['from' => 'draft', 'to' => 'sent', 'name' => 'Send Invoice', 'code' => 'send', 'description' => 'Mengirim invoice ke pelanggan.', 'required_permission' => 'customer_invoice.edit', 'sort_order' => 10, 'actions' => [['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'sent']]]],
            ['from' => 'sent', 'to' => 'partially_paid', 'name' => 'Record Partial Payment', 'code' => 'partial_payment', 'description' => 'Mencatat pembayaran sebagian.', 'required_permission' => 'customer_invoice.edit', 'sort_order' => 20, 'actions' => [['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'partially_paid']]]],
            ['from' => 'sent', 'to' => 'paid', 'name' => 'Record Full Payment', 'code' => 'full_payment', 'description' => 'Mencatat pembayaran lunas.', 'required_permission' => 'customer_invoice.edit', 'sort_order' => 30, 'actions' => [['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'paid']]]],
            ['from' => 'sent', 'to' => 'overdue', 'name' => 'Mark as Overdue', 'code' => 'mark_overdue', 'description' => 'Menandai invoice sebagai overdue.', 'required_permission' => 'customer_invoice.edit', 'sort_order' => 40, 'actions' => [['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'overdue']]]],
            ['from' => 'partially_paid', 'to' => 'paid', 'name' => 'Complete Payment', 'code' => 'complete_payment', 'description' => 'Menyelesaikan pembayaran menjadi lunas.', 'required_permission' => 'customer_invoice.edit', 'sort_order' => 10, 'actions' => [['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'paid']]]],
            ['from' => 'partially_paid', 'to' => 'overdue', 'name' => 'Mark as Overdue', 'code' => 'partial_overdue', 'description' => 'Menandai invoice sebagai overdue meskipun sudah dibayar sebagian.', 'required_permission' => 'customer_invoice.edit', 'sort_order' => 20, 'actions' => [['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'overdue']]]],
            ['from' => 'overdue', 'to' => 'partially_paid', 'name' => 'Record Partial Payment', 'code' => 'overdue_partial', 'description' => 'Mencatat pembayaran sebagian untuk invoice overdue.', 'required_permission' => 'customer_invoice.edit', 'sort_order' => 10, 'actions' => [['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'partially_paid']]]],
            ['from' => 'overdue', 'to' => 'paid', 'name' => 'Record Full Payment', 'code' => 'overdue_full', 'description' => 'Mencatat pembayaran lunas untuk invoice overdue.', 'required_permission' => 'customer_invoice.edit', 'sort_order' => 20, 'actions' => [['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'paid']]]],
            ['from' => 'draft', 'to' => 'cancelled', 'name' => 'Cancel Invoice', 'code' => 'cancel', 'description' => 'Membatalkan invoice sebelum dikirim.', 'required_permission' => 'customer_invoice.edit', 'sort_order' => 20, 'actions' => [['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'cancelled']]]],
            ['from' => 'sent', 'to' => 'void', 'name' => 'Void Invoice', 'code' => 'void', 'description' => 'Membatalkan invoice setelah dikirim. Memerlukan konfirmasi dan komentar.', 'required_permission' => 'customer_invoice.edit', 'sort_order' => 50, 'actions' => [['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'void']]]],
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
                    'requires_confirmation' => $tData['requires_confirmation'] ?? false,
                    'requires_comment' => $tData['requires_comment'] ?? false,
                    'requires_approval' => false,
                ])
            );

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
    }

    private function seedAssetLifecycle(?int $adminUserId): void
    {
        $pipeline = Pipeline::firstOrCreate(
            ['code' => 'asset_lifecycle'],
            [
                'name' => 'Asset Lifecycle',
                'code' => 'asset_lifecycle',
                'entity_type' => 'App\Models\Asset',
                'description' => 'Mengelola siklus hidup aset — dari draft, aktif, maintenance, hingga disposed/lost/cancelled.',
                'version' => 1,
                'is_active' => true,
                'conditions' => null,
                'created_by' => $adminUserId,
            ]
        );

        $statesData = [
            ['code' => 'draft', 'name' => 'Draft', 'type' => 'initial', 'color' => '#6B7280', 'icon' => 'FileEdit', 'description' => 'Aset baru didaftarkan, belum aktif.', 'sort_order' => 0],
            ['code' => 'active', 'name' => 'Active', 'type' => 'intermediate', 'color' => '#10B981', 'icon' => 'CircleCheck', 'description' => 'Aset aktif digunakan.', 'sort_order' => 10],
            ['code' => 'maintenance', 'name' => 'In Maintenance', 'type' => 'intermediate', 'color' => '#F59E0B', 'icon' => 'Wrench', 'description' => 'Aset sedang dalam perawatan.', 'sort_order' => 20],
            ['code' => 'disposed', 'name' => 'Disposed', 'type' => 'final', 'color' => '#EF4444', 'icon' => 'Trash2', 'description' => 'Aset dihapusbukukan.', 'sort_order' => 30],
            ['code' => 'lost', 'name' => 'Lost', 'type' => 'final', 'color' => '#DC2626', 'icon' => 'AlertTriangle', 'description' => 'Aset hilang.', 'sort_order' => 40],
            ['code' => 'cancelled', 'name' => 'Cancelled', 'type' => 'final', 'color' => '#9CA3AF', 'icon' => 'Ban', 'description' => 'Pendaftaran aset dibatalkan.', 'sort_order' => 50],
        ];

        $states = [];
        foreach ($statesData as $stateData) {
            $states[$stateData['code']] = PipelineState::firstOrCreate(
                ['pipeline_id' => $pipeline->id, 'code' => $stateData['code']],
                array_merge($stateData, ['pipeline_id' => $pipeline->id])
            );
        }

        $transitionsData = [
            ['from' => 'draft', 'to' => 'active', 'name' => 'Activate', 'code' => 'activate', 'requires_confirmation' => true, 'requires_comment' => false],
            ['from' => 'draft', 'to' => 'cancelled', 'name' => 'Cancel', 'code' => 'cancel', 'requires_confirmation' => true, 'requires_comment' => false],
            ['from' => 'active', 'to' => 'maintenance', 'name' => 'Send to Maintenance', 'code' => 'send_to_maintenance', 'requires_confirmation' => false, 'requires_comment' => true],
            ['from' => 'active', 'to' => 'disposed', 'name' => 'Dispose', 'code' => 'dispose', 'requires_confirmation' => true, 'requires_comment' => true],
            ['from' => 'active', 'to' => 'lost', 'name' => 'Mark as Lost', 'code' => 'mark_as_lost', 'requires_confirmation' => true, 'requires_comment' => true],
            ['from' => 'maintenance', 'to' => 'active', 'name' => 'Return from Maintenance', 'code' => 'return_from_maintenance', 'requires_confirmation' => false, 'requires_comment' => false],
        ];

        foreach ($transitionsData as $tData) {
            PipelineTransition::firstOrCreate(
                [
                    'pipeline_id' => $pipeline->id,
                    'from_state_id' => $states[$tData['from']]->id,
                    'to_state_id' => $states[$tData['to']]->id,
                ],
                [
                    'pipeline_id' => $pipeline->id,
                    'name' => $tData['name'],
                    'code' => $tData['code'],
                    'from_state_id' => $states[$tData['from']]->id,
                    'to_state_id' => $states[$tData['to']]->id,
                    'requires_confirmation' => $tData['requires_confirmation'],
                    'requires_comment' => $tData['requires_comment'],
                    'is_active' => true,
                ]
            );
        }
    }
}
