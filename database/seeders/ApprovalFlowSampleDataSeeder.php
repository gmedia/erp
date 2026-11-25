<?php

namespace Database\Seeders;

use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\Asset;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class ApprovalFlowSampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', config('app.admin'))->first();
        $hrManager = User::where('email', 'manager.hr@example.com')->first();
        $financeDirector = User::where('email', 'director.finance@example.com')->first();

        if (! $admin || ! $hrManager || ! $financeDirector) {
            return;
        }

        $flows = [
            [
                'code' => 'asset_registration_high_value',
                'name' => 'Pendaftaran Aset Bernilai Tinggi',
                'approvable_type' => Asset::class,
                'description' => 'Persetujuan pendaftaran aset dengan harga perolehan di atas Rp 100 juta',
                'conditions' => [
                    'field_checks' => [
                        ['field' => 'purchase_cost', 'operator' => '>', 'value' => 100000000],
                    ],
                ],
                'steps' => [
                    [
                        'step_order' => 1,
                        'name' => 'Review Manajer HR',
                        'approver_user_id' => $hrManager->id,
                    ],
                    [
                        'step_order' => 2,
                        'name' => 'Persetujuan Direktur Keuangan',
                        'approver_user_id' => $financeDirector->id,
                    ],
                ],
            ],
            [
                'code' => 'purchase_request_default',
                'name' => 'Persetujuan Default Permintaan Pembelian',
                'approvable_type' => PurchaseRequest::class,
                'description' => 'Alur persetujuan default untuk permintaan pembelian yang diajukan',
                'conditions' => null,
                'steps' => [
                    [
                        'step_order' => 1,
                        'name' => 'Review Admin',
                        'approver_user_id' => $admin->id,
                    ],
                ],
            ],
            [
                'code' => 'purchase_order_default',
                'name' => 'Persetujuan Default Pesanan Pembelian',
                'approvable_type' => PurchaseOrder::class,
                'description' => 'Alur persetujuan default untuk pesanan pembelian yang diajukan',
                'conditions' => null,
                'steps' => [
                    [
                        'step_order' => 1,
                        'name' => 'Persetujuan Admin',
                        'approver_user_id' => $admin->id,
                    ],
                ],
            ],
        ];

        foreach ($flows as $flowDefinition) {
            $steps = $flowDefinition['steps'];

            // Master dibuat sebelum transaksi pertama (approval request paling awal ~35 hari lalu).
            // created_at hanya saat insert, agar seed ulang tidak menggeser tanggal master.
            $existing = ApprovalFlow::query()->where('code', $flowDefinition['code'])->exists();

            $flow = ApprovalFlow::updateOrCreate(
                ['code' => $flowDefinition['code']],
                [
                    'name' => $flowDefinition['name'],
                    'approvable_type' => $flowDefinition['approvable_type'],
                    'description' => $flowDefinition['description'],
                    'is_active' => true,
                    'conditions' => $flowDefinition['conditions'],
                    'created_by' => $admin->id,
                ] + ($existing ? [] : ['created_at' => now()->subDays(60)])
            );

            foreach ($steps as $stepDefinition) {
                ApprovalFlowStep::updateOrCreate(
                    [
                        'approval_flow_id' => $flow->id,
                        'step_order' => $stepDefinition['step_order'],
                    ],
                    [
                        'name' => $stepDefinition['name'],
                        'approver_type' => 'user',
                        'approver_user_id' => $stepDefinition['approver_user_id'],
                    ]
                );
            }
        }
    }
}
