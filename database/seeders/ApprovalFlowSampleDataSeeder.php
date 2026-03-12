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
        $hrManager = User::where('email', 'manager.hr@dokfin.id')->first();
        $financeDirector = User::where('email', 'director.finance@dokfin.id')->first();

        if (! $admin || ! $hrManager || ! $financeDirector) {
            return;
        }

        $flows = [
            [
                'code' => 'asset_registration_high_value',
                'name' => 'High Value Asset Registration',
                'approvable_type' => Asset::class,
                'description' => 'Approval for registering assets with cost > 100M',
                'conditions' => ['field_checks' => [['field' => 'purchase_cost', 'operator' => '>', 'value' => 100000000]]],
                'steps' => [
                    [
                        'step_order' => 1,
                        'name' => 'HR Manager Review',
                        'approver_user_id' => $hrManager->id,
                    ],
                    [
                        'step_order' => 2,
                        'name' => 'Finance Director Approval',
                        'approver_user_id' => $financeDirector->id,
                    ],
                ],
            ],
            [
                'code' => 'purchase_request_default',
                'name' => 'Purchase Request Default Approval',
                'approvable_type' => PurchaseRequest::class,
                'description' => 'Default approval flow for submitted purchase requests',
                'conditions' => null,
                'steps' => [
                    [
                        'step_order' => 1,
                        'name' => 'Admin Review',
                        'approver_user_id' => $admin->id,
                    ],
                ],
            ],
            [
                'code' => 'purchase_order_default',
                'name' => 'Purchase Order Default Approval',
                'approvable_type' => PurchaseOrder::class,
                'description' => 'Default approval flow for submitted purchase orders',
                'conditions' => null,
                'steps' => [
                    [
                        'step_order' => 1,
                        'name' => 'Admin Approval',
                        'approver_user_id' => $admin->id,
                    ],
                ],
            ],
        ];

        foreach ($flows as $flowDefinition) {
            $steps = $flowDefinition['steps'];

            $flow = ApprovalFlow::updateOrCreate(
                ['code' => $flowDefinition['code']],
                [
                    'name' => $flowDefinition['name'],
                    'approvable_type' => $flowDefinition['approvable_type'],
                    'description' => $flowDefinition['description'],
                    'is_active' => true,
                    'conditions' => $flowDefinition['conditions'],
                    'created_by' => $admin->id,
                ]
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
