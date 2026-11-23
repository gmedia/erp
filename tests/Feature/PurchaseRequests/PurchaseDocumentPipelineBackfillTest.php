<?php

use App\Models\ApprovalFlow;
use App\Models\Pipeline;
use App\Models\PipelineEntityState;
use App\Models\PipelineState;
use App\Models\PipelineTransitionAction;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class)->group('purchase-requests');

/**
 * Pipeline pr_lifecycle + po_lifecycle dengan state yang dipakai backfill.
 */
function setUpPurchaseDocumentPipelines(): void
{
    foreach ([
        ['code' => 'pr_lifecycle', 'entity_type' => PurchaseRequest::class, 'states' => ['draft', 'pending_approval', 'approved', 'rejected']],
        ['code' => 'po_lifecycle', 'entity_type' => PurchaseOrder::class, 'states' => ['draft', 'pending_approval', 'confirmed', 'rejected']],
    ] as $definition) {
        $pipeline = Pipeline::factory()->create([
            'code' => $definition['code'],
            'entity_type' => $definition['entity_type'],
            'is_active' => true,
        ]);

        foreach ($definition['states'] as $index => $code) {
            PipelineState::factory()->create([
                'pipeline_id' => $pipeline->id,
                'code' => $code,
                'type' => $index === 0 ? 'initial' : 'intermediate',
                'sort_order' => $index * 10,
            ]);
        }
    }
}

/**
 * Jalankan file migrasi backfill secara langsung.
 */
function runPurchaseDocumentPipelineBackfill(): void
{
    $migration = require database_path('migrations/2026_11_23_110000_backfill_pipeline_state_for_purchase_documents.php');
    $migration->up();
}

beforeEach(function () {
    setUpPurchaseDocumentPipelines();
});

test('backfill creates pipeline state from existing status without changing status', function () {
    $pr = PurchaseRequest::factory()->create(['status' => 'approved']);
    $po = PurchaseOrder::factory()->create(['status' => 'confirmed']);

    // Simulasikan data lama: hapus state yang dibuat otomatis oleh model hook.
    PipelineEntityState::query()->delete();

    runPurchaseDocumentPipelineBackfill();

    $prState = PipelineEntityState::query()
        ->where('entity_type', PurchaseRequest::class)
        ->where('entity_id', $pr->id)
        ->with('currentState')
        ->first();
    $poState = PipelineEntityState::query()
        ->where('entity_type', PurchaseOrder::class)
        ->where('entity_id', $po->id)
        ->with('currentState')
        ->first();

    expect($prState->currentState->code)->toBe('approved')
        ->and($poState->currentState->code)->toBe('confirmed')
        ->and($pr->fresh()->status)->toBe('approved')
        ->and($po->fresh()->status)->toBe('confirmed');
});

test('backfill is idempotent and skips documents that already have a pipeline state', function () {
    $pr = PurchaseRequest::factory()->create(['status' => 'draft']);
    PipelineEntityState::query()->where('entity_id', $pr->id)->update([
        'current_state_id' => PipelineState::query()->where('code', 'pending_approval')->value('id'),
    ]);

    runPurchaseDocumentPipelineBackfill();
    runPurchaseDocumentPipelineBackfill();

    $states = PipelineEntityState::query()
        ->where('entity_type', PurchaseRequest::class)
        ->where('entity_id', $pr->id)
        ->get();

    expect($states)->toHaveCount(1)
        ->and($states->first()->currentState->code)->toBe('pending_approval')
        ->and($pr->fresh()->status)->toBe('draft');
});

test('backfill falls back to the initial state when status is not part of the pipeline', function () {
    $pr = PurchaseRequest::factory()->create(['status' => 'fully_ordered']);
    PipelineEntityState::query()->delete();

    runPurchaseDocumentPipelineBackfill();

    $state = PipelineEntityState::query()
        ->where('entity_type', PurchaseRequest::class)
        ->where('entity_id', $pr->id)
        ->with('currentState')
        ->first();

    expect($state->currentState->code)->toBe('draft')
        ->and($pr->fresh()->status)->toBe('fully_ordered');
});

test('demo seeder routes purchase document submits through the default approval flows', function () {
    seed(DatabaseSeeder::class);

    $flowCodes = PipelineTransitionAction::query()
        ->where('action_type', 'trigger_approval')
        ->get()
        ->map(fn (PipelineTransitionAction $action) => $action->config['flow_code'] ?? null)
        ->filter()
        ->unique()
        ->values()
        ->all();

    expect($flowCodes)->toContain('purchase_request_default')
        ->and($flowCodes)->toContain('purchase_order_default')
        ->and(ApprovalFlow::query()->whereIn('code', ['purchase_request_default', 'purchase_order_default'])->count())
        ->toBe(2);
});

test('demo seeder is idempotent for purchase document pipeline states', function () {
    seed(DatabaseSeeder::class);
    $before = PipelineEntityState::query()
        ->whereIn('entity_type', [PurchaseRequest::class, PurchaseOrder::class])
        ->count();

    seed(DatabaseSeeder::class);
    $after = PipelineEntityState::query()
        ->whereIn('entity_type', [PurchaseRequest::class, PurchaseOrder::class])
        ->count();

    expect($after)->toBe($before);
});
