<?php

namespace Database\Seeders;

use App\Models\Pipeline;
use App\Models\PipelineState;
use App\Models\PipelineTransition;
use App\Models\PipelineTransitionAction;
use App\Models\User;
use Illuminate\Database\Seeder;

class PipelineSeeder extends Seeder
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
        $adminUserId = User::where('email', config('app.admin'))->value('id');

        // ── Pipeline ────────────────────────────────────────────────
        $pipeline = Pipeline::firstOrCreate(
            ['code' => 'asset_lifecycle'],
            [
                'name'        => 'Asset Lifecycle',
                'code'        => 'asset_lifecycle',
                'entity_type' => 'App\Models\Asset',
                'description' => 'Mengelola siklus hidup aset perusahaan — dari draft, aktif, maintenance, hingga disposed atau lost.',
                'version'     => 1,
                'is_active'   => true,
                'conditions'  => null,
                'created_by'  => $adminUserId,
            ]
        );

        // ── States ──────────────────────────────────────────────────
        $statesData = [
            [
                'code'        => 'draft',
                'name'        => 'Draft',
                'type'        => 'initial',
                'color'       => '#6B7280',
                'icon'        => 'FileEdit',
                'description' => 'Aset baru dibuat, belum aktif digunakan.',
                'sort_order'  => 0,
            ],
            [
                'code'        => 'active',
                'name'        => 'Active',
                'type'        => 'intermediate',
                'color'       => '#10B981',
                'icon'        => 'CircleCheck',
                'description' => 'Aset aktif dan sedang digunakan.',
                'sort_order'  => 10,
            ],
            [
                'code'        => 'maintenance',
                'name'        => 'In Maintenance',
                'type'        => 'intermediate',
                'color'       => '#F59E0B',
                'icon'        => 'Wrench',
                'description' => 'Aset sedang dalam perbaikan atau perawatan.',
                'sort_order'  => 20,
            ],
            [
                'code'        => 'disposed',
                'name'        => 'Disposed',
                'type'        => 'final',
                'color'       => '#EF4444',
                'icon'        => 'Trash2',
                'description' => 'Aset telah dilepas atau dihapusbukukan.',
                'sort_order'  => 30,
            ],
            [
                'code'        => 'lost',
                'name'        => 'Lost',
                'type'        => 'final',
                'color'       => '#DC2626',
                'icon'        => 'AlertTriangle',
                'description' => 'Aset hilang dan tidak dapat ditemukan.',
                'sort_order'  => 40,
            ],
            [
                'code'        => 'cancelled',
                'name'        => 'Cancelled',
                'type'        => 'final',
                'color'       => '#9CA3AF',
                'icon'        => 'XCircle',
                'description' => 'Pendaftaran aset dibatalkan sebelum diaktifkan.',
                'sort_order'  => 50,
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
                'from'                  => 'draft',
                'to'                    => 'active',
                'name'                  => 'Activate',
                'code'                  => 'activate',
                'description'           => 'Mengaktifkan aset untuk mulai digunakan.',
                'required_permission'   => 'asset.edit',
                'guard_conditions'      => null,
                'requires_confirmation' => false,
                'requires_comment'      => false,
                'requires_approval'     => false,
                'sort_order'            => 10,
                'actions' => [
                    ['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'active']],
                ],
            ],
            [
                'from'                  => 'draft',
                'to'                    => 'cancelled',
                'name'                  => 'Cancel',
                'code'                  => 'cancel',
                'description'           => 'Membatalkan pendaftaran aset yang belum aktif.',
                'required_permission'   => 'asset.edit',
                'guard_conditions'      => null,
                'requires_confirmation' => true,
                'requires_comment'      => false,
                'requires_approval'     => false,
                'sort_order'            => 20,
                'actions' => [
                    ['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'cancelled']],
                ],
            ],
            [
                'from'                  => 'active',
                'to'                    => 'maintenance',
                'name'                  => 'Send to Maintenance',
                'code'                  => 'send_maintenance',
                'description'           => 'Mengirim aset untuk perbaikan atau perawatan.',
                'required_permission'   => 'asset.edit',
                'guard_conditions'      => null,
                'requires_confirmation' => false,
                'requires_comment'      => false,
                'requires_approval'     => false,
                'sort_order'            => 10,
                'actions' => [
                    ['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'maintenance']],
                ],
            ],
            [
                'from'                  => 'maintenance',
                'to'                    => 'active',
                'name'                  => 'Return from Maintenance',
                'code'                  => 'return_maintenance',
                'description'           => 'Mengembalikan aset ke status aktif setelah perbaikan selesai.',
                'required_permission'   => 'asset.edit',
                'guard_conditions'      => null,
                'requires_confirmation' => false,
                'requires_comment'      => false,
                'requires_approval'     => false,
                'sort_order'            => 10,
                'actions' => [
                    ['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'active']],
                ],
            ],
            [
                'from'                  => 'active',
                'to'                    => 'disposed',
                'name'                  => 'Dispose',
                'code'                  => 'dispose',
                'description'           => 'Melepas atau menghapusbukukan aset. Memerlukan konfirmasi dan alasan.',
                'required_permission'   => 'asset.edit',
                'guard_conditions'      => null,
                'requires_confirmation' => true,
                'requires_comment'      => true,
                'requires_approval'     => false,
                'sort_order'            => 20,
                'actions' => [
                    ['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'disposed']],
                ],
            ],
            [
                'from'                  => 'active',
                'to'                    => 'lost',
                'name'                  => 'Mark as Lost',
                'code'                  => 'mark_lost',
                'description'           => 'Menandai aset sebagai hilang. Memerlukan konfirmasi dan keterangan.',
                'required_permission'   => 'asset.edit',
                'guard_conditions'      => null,
                'requires_confirmation' => true,
                'requires_comment'      => true,
                'requires_approval'     => false,
                'sort_order'            => 30,
                'actions' => [
                    ['action_type' => 'update_field', 'execution_order' => 10, 'config' => ['field' => 'status', 'value' => 'lost']],
                ],
            ],
        ];

        foreach ($transitionsData as $tData) {
            $fromState = $states[$tData['from']];
            $toState   = $states[$tData['to']];
            $actions   = $tData['actions'];

            unset($tData['from'], $tData['to'], $tData['actions']);

            $transition = PipelineTransition::firstOrCreate(
                [
                    'pipeline_id'   => $pipeline->id,
                    'from_state_id' => $fromState->id,
                    'to_state_id'   => $toState->id,
                ],
                array_merge($tData, [
                    'pipeline_id'   => $pipeline->id,
                    'from_state_id' => $fromState->id,
                    'to_state_id'   => $toState->id,
                    'is_active'     => true,
                ])
            );

            // ── Transition Actions ──────────────────────────────────
            foreach ($actions as $actionData) {
                PipelineTransitionAction::firstOrCreate(
                    [
                        'pipeline_transition_id' => $transition->id,
                        'execution_order'        => $actionData['execution_order'],
                    ],
                    array_merge($actionData, [
                        'pipeline_transition_id' => $transition->id,
                        'is_async'               => false,
                        'on_failure'             => 'abort',
                        'is_active'              => true,
                    ])
                );
            }
        }
    }
}
