<?php

namespace Database\Seeders;

use App\Models\Pipeline;
use App\Models\User;
use Illuminate\Database\Seeder;

class PipelineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUserId = User::where('email', 'admin@example.com')->value('id');
        if (!$adminUserId) {
            $adminUserId = User::factory()->create(['email' => 'admin@example.com'])->id;
        }

        $pipelines = [
            [
                'name' => 'Asset Lifecycle',
                'code' => 'asset_lifecycle',
                'entity_type' => 'App\Models\Asset',
                'description' => 'Standard lifecycle for company assets',
                'version' => 1,
                'is_active' => true,
                'conditions' => null,
                'created_by' => $adminUserId,
            ],
            [
                'name' => 'Purchase Order Flow',
                'code' => 'po_flow',
                'entity_type' => 'App\Models\PurchaseOrder',
                'description' => 'Approval and receiving flow for PO',
                'version' => 1,
                'is_active' => true,
                'conditions' => null,
                'created_by' => $adminUserId,
            ],
            [
                'name' => 'Purchase Request Flow',
                'code' => 'pr_flow',
                'entity_type' => 'App\Models\PurchaseRequest',
                'description' => 'Approval flow for PR',
                'version' => 1,
                'is_active' => true,
                'conditions' => null,
                'created_by' => $adminUserId,
            ],
        ];

        foreach ($pipelines as $pipelineData) {
            Pipeline::firstOrCreate(
                ['code' => $pipelineData['code']],
                $pipelineData
            );
        }

        Pipeline::factory()->count(10)->create([
            'created_by' => $adminUserId,
        ]);
    }
}
