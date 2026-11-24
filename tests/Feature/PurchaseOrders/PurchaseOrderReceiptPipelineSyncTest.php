<?php

use App\Models\GoodsReceipt;
use App\Models\Pipeline;
use App\Models\PipelineEntityState;
use App\Models\PipelineState;
use App\Models\PipelineTransition;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('purchase-orders');

/**
 * State pipeline po_lifecycle harus selalu sama dengan status PO setelah
 * penerimaan barang (GR confirmed / dibatalkan).
 *
 * @return array{pipeline: Pipeline, states: array<string, PipelineState>}
 */
function receiptSyncBuildPipeline(): array
{
    $pipeline = Pipeline::factory()->create([
        'code' => 'po_lifecycle',
        'entity_type' => PurchaseOrder::class,
        'is_active' => true,
    ]);

    $states = [];
    foreach ([
        'draft' => 'initial',
        'confirmed' => 'intermediate',
        'partially_received' => 'intermediate',
        'fully_received' => 'intermediate',
        'cancelled' => 'final',
    ] as $code => $type) {
        $states[$code] = PipelineState::factory()->create([
            'pipeline_id' => $pipeline->id,
            'code' => $code,
            'type' => $type,
            'sort_order' => count($states) * 10,
        ]);
    }

    return ['pipeline' => $pipeline, 'states' => $states];
}

/**
 * PO confirmed dengan satu baris (qty 10) dan state pipeline yang sudah disinkronkan ke confirmed.
 *
 * @return array{po: PurchaseOrder, line: PurchaseOrderItem, product: Product, unit: Unit, states: array<string, PipelineState>, pipeline: Pipeline}
 */
function receiptSyncSetUpConfirmedPo(): array
{
    // Issue #416: konfirmasi GR membutuhkan COA aktif dan periode fiskal.
    createAccountingControlFixture();

    $setup = receiptSyncBuildPipeline();

    $po = PurchaseOrder::factory()->create(['status' => 'confirmed']);
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();

    $line = $po->items()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity' => 10,
        'unit_price' => 5000,
        'discount_percent' => 0,
        'tax_percent' => 11,
        'line_total' => 55500,
    ]);

    receiptSyncSetPipelineState($po, $setup['states']['confirmed']);

    return [
        'po' => $po,
        'line' => $line,
        'product' => $product,
        'unit' => $unit,
        'states' => $setup['states'],
        'pipeline' => $setup['pipeline'],
    ];
}

function receiptSyncSetPipelineState(PurchaseOrder $po, PipelineState $state): void
{
    PipelineEntityState::query()
        ->where('entity_type', PurchaseOrder::class)
        ->where('entity_id', $po->id)
        ->firstOrFail()
        ->update(['current_state_id' => $state->id]);
}

function receiptSyncPipelineStateCode(PurchaseOrder $po): ?string
{
    return PipelineEntityState::query()
        ->where('entity_type', PurchaseOrder::class)
        ->where('entity_id', $po->id)
        ->first()
        ?->currentState?->code;
}

/**
 * GR draft untuk PO, lalu dikonfirmasi lewat API dengan qty accepted tertentu.
 */
function receiptSyncConfirmGoodsReceipt(array $setup, float $accepted): GoodsReceipt
{
    $goodsReceipt = GoodsReceipt::factory()->create([
        'purchase_order_id' => $setup['po']->id,
        'warehouse_id' => $setup['po']->warehouse_id,
        'receipt_date' => now()->toDateString(),
        'status' => 'draft',
        'confirmed_at' => null,
        'confirmed_by' => null,
    ]);

    putJson('/api/goods-receipts/' . $goodsReceipt->id, [
        'status' => 'confirmed',
        'items' => [
            [
                'purchase_order_item_id' => $setup['line']->id,
                'product_id' => $setup['product']->id,
                'unit_id' => $setup['unit']->id,
                'quantity_received' => $accepted,
                'quantity_accepted' => $accepted,
                'quantity_rejected' => 0,
                'unit_price' => 5000,
            ],
        ],
    ])->assertOk();

    return $goodsReceipt->fresh();
}

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'pipeline',
        'purchase_order.edit',
        'goods_receipt',
        'goods_receipt.edit',
    ]);
    Sanctum::actingAs($user, ['*']);
});

test('confirming a partial goods receipt moves the pipeline state to partially_received', function () {
    $setup = receiptSyncSetUpConfirmedPo();

    receiptSyncConfirmGoodsReceipt($setup, 4);

    expect($setup['po']->fresh()->status)->toBe('partially_received')
        ->and(receiptSyncPipelineStateCode($setup['po']))->toBe('partially_received');
});

test('confirming a full goods receipt moves the pipeline state to fully_received', function () {
    $setup = receiptSyncSetUpConfirmedPo();

    receiptSyncConfirmGoodsReceipt($setup, 10);

    expect($setup['po']->fresh()->status)->toBe('fully_received')
        ->and(receiptSyncPipelineStateCode($setup['po']))->toBe('fully_received');
});

test('cancelling a confirmed goods receipt returns the pipeline state to the restored status', function () {
    $setup = receiptSyncSetUpConfirmedPo();

    $goodsReceipt = receiptSyncConfirmGoodsReceipt($setup, 4);
    expect(receiptSyncPipelineStateCode($setup['po']))->toBe('partially_received');

    putJson('/api/goods-receipts/' . $goodsReceipt->id, [
        'status' => 'cancelled',
    ])->assertOk();

    expect($setup['po']->fresh()->status)->toBe('confirmed')
        ->and(receiptSyncPipelineStateCode($setup['po']))->toBe('confirmed');
});

test('cancel transition on a fully_received purchase order is rejected and leaves the state unchanged', function () {
    $setup = receiptSyncSetUpConfirmedPo();

    receiptSyncConfirmGoodsReceipt($setup, 10);
    expect(receiptSyncPipelineStateCode($setup['po']))->toBe('fully_received');

    $cancel = PipelineTransition::factory()->create([
        'pipeline_id' => $setup['pipeline']->id,
        'from_state_id' => $setup['states']['confirmed']->id,
        'to_state_id' => $setup['states']['cancelled']->id,
        'name' => 'Cancel',
        'code' => 'cancel',
        'required_permission' => null,
    ]);

    postJson('/api/entity-states/purchase_order/' . $setup['po']->id . '/transition', [
        'transition_id' => $cancel->id,
    ])->assertUnprocessable();

    expect($setup['po']->fresh()->status)->toBe('fully_received')
        ->and(receiptSyncPipelineStateCode($setup['po']))->toBe('fully_received');
});
