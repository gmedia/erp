<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Backfill pipeline state untuk Purchase Request dan Purchase Order lama
 * yang belum punya pipeline_entity_states (dibuat sebelum #368).
 *
 * Status dokumen TIDAK diubah. State pipeline diambil dari status yang ada
 * bila kode state tersebut terdefinisi di pipeline aktif entity itu.
 */
return new class extends Migration
{
    /** @var array<string, string> entity_type => pipeline code */
    private const PIPELINES = [
        'App\\Models\\PurchaseRequest' => ['table' => 'purchase_requests', 'pipeline' => 'pr_lifecycle'],
        'App\\Models\\PurchaseOrder' => ['table' => 'purchase_orders', 'pipeline' => 'po_lifecycle'],
    ];

    public function up(): void
    {
        foreach (self::PIPELINES as $entityType => $config) {
            $pipeline = DB::table('pipelines')
                ->where('code', $config['pipeline'])
                ->where('entity_type', $entityType)
                ->where('is_active', true)
                ->first();

            if (! $pipeline) {
                continue;
            }

            $initialState = DB::table('pipeline_states')
                ->where('pipeline_id', $pipeline->id)
                ->where('type', 'initial')
                ->first();

            if (! $initialState) {
                continue;
            }

            $statesByCode = DB::table('pipeline_states')
                ->where('pipeline_id', $pipeline->id)
                ->pluck('id', 'code');

            DB::table($config['table'])
                ->whereNotIn('id', function ($query) use ($entityType, $pipeline) {
                    $query->select('entity_id')
                        ->from('pipeline_entity_states')
                        ->where('entity_type', $entityType)
                        ->where('pipeline_id', $pipeline->id);
                })
                ->orderBy('id')
                ->chunkById(500, function ($documents) use ($entityType, $pipeline, $statesByCode) {
                    $now = now();

                    foreach ($documents as $document) {
                        // Status tanpa state di pipeline ini (mis. approved yang tidak terdefinisi)
                        // dilewati dan dicatat, supaya tidak tampil sebagai draft yang bisa diajukan ulang.
                        if (! isset($statesByCode[$document->status])) {
                            Log::warning(
                                'Backfill pipeline state dilewati: status tanpa state di pipeline. '
                                . "Entity: {$entityType} ID: {$document->id} Status: {$document->status}"
                            );

                            continue;
                        }

                        $stateId = $statesByCode[$document->status];

                        $entityStateId = DB::table('pipeline_entity_states')->insertGetId([
                            'pipeline_id' => $pipeline->id,
                            'entity_type' => $entityType,
                            'entity_id' => $document->id,
                            'current_state_id' => $stateId,
                            'last_transitioned_by' => null,
                            'last_transitioned_at' => $now,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);

                        DB::table('pipeline_state_logs')->insert([
                            'pipeline_entity_state_id' => $entityStateId,
                            'entity_type' => $entityType,
                            'entity_id' => $document->id,
                            'from_state_id' => null,
                            'to_state_id' => $stateId,
                            'transition_id' => null,
                            'performed_by' => null,
                            'comment' => 'Backfill pipeline state from existing status (#368)',
                            'created_at' => $now,
                        ]);
                    }
                }, 'id');
        }
    }

    public function down(): void
    {
        // Backfill hanya menambah baris; tidak ada data lama yang diubah.
    }
};
