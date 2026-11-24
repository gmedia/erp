<?php

use App\Models\ArReceipt;
use App\Models\AssetDepreciationRun;
use App\Models\BankReconciliation;
use App\Models\Budget;
use App\Models\CreditNote;
use App\Models\CustomerInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\RecurringJournal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('purchasing-finance-permissions');

/*
 * Matriks permission per route untuk modul purchasing & keuangan.
 * Konvensi: GET = modul, POST create/import = modul.create, PUT = modul.edit,
 * DELETE = modul.delete, aksi khusus = modul.edit.
 *
 * Format: izin induk => [model {id} (atau null), [METHOD, sub-path, izin]].
 * {id} diisi record nyata dari factory saat test berjalan, agar route-model
 * binding berhasil dan middleware yang menentukan hasilnya.
 */
$matrix = [
    'purchase_order' => [PurchaseOrder::class, 'purchase-orders', [
        ['GET', '', 'purchase_order'],
        ['POST', '', 'purchase_order.create'],
        ['PUT', '/{id}', 'purchase_order.edit'],
        ['DELETE', '/{id}', 'purchase_order.delete'],
    ]],
    'purchase_request' => [PurchaseRequest::class, 'purchase-requests', [
        ['GET', '', 'purchase_request'],
        ['POST', '', 'purchase_request.create'],
        ['PUT', '/{id}', 'purchase_request.edit'],
        ['DELETE', '/{id}', 'purchase_request.delete'],
    ]],
    'customer_invoice' => [CustomerInvoice::class, 'customer-invoices', [
        ['GET', '', 'customer_invoice'],
        ['POST', '', 'customer_invoice.create'],
        ['PUT', '/{id}', 'customer_invoice.edit'],
        ['DELETE', '/{id}', 'customer_invoice.delete'],
    ]],
    'ar_receipt' => [ArReceipt::class, 'ar-receipts', [
        ['GET', '', 'ar_receipt'],
        ['POST', '', 'ar_receipt.create'],
        ['PUT', '/{id}', 'ar_receipt.edit'],
        ['DELETE', '/{id}', 'ar_receipt.delete'],
    ]],
    'credit_note' => [CreditNote::class, 'credit-notes', [
        ['GET', '', 'credit_note'],
        ['POST', '', 'credit_note.create'],
        ['PUT', '/{id}', 'credit_note.edit'],
        ['DELETE', '/{id}', 'credit_note.delete'],
        ['POST', '/{id}/apply', 'credit_note.edit'],
    ]],
    'budget' => [Budget::class, 'budgets', [
        ['GET', '', 'budget'],
        ['POST', '', 'budget.create'],
        ['PUT', '/{id}', 'budget.edit'],
        ['DELETE', '/{id}', 'budget.delete'],
        ['POST', '/{id}/approve', 'budget.edit'],
        ['POST', '/{id}/lock', 'budget.edit'],
    ]],
    'bank_reconciliation' => [BankReconciliation::class, 'bank-reconciliations', [
        ['GET', '', 'bank_reconciliation'],
        ['POST', '', 'bank_reconciliation.create'],
        ['PUT', '/{id}', 'bank_reconciliation.edit'],
        ['DELETE', '/{id}', 'bank_reconciliation.delete'],
        ['POST', '/{id}/complete', 'bank_reconciliation.edit'],
    ]],
    'recurring_journal' => [RecurringJournal::class, 'recurring-journals', [
        ['GET', '', 'recurring_journal'],
        ['POST', '', 'recurring_journal.create'],
        ['PUT', '/{id}', 'recurring_journal.edit'],
        ['DELETE', '/{id}', 'recurring_journal.delete'],
        ['POST', '/{id}/execute', 'recurring_journal.edit'],
    ]],
    'asset_depreciation_run' => [AssetDepreciationRun::class, 'asset-depreciation-runs', [
        ['GET', '', 'asset_depreciation_run'],
        ['POST', '/calculate', 'asset_depreciation_run.edit'],
        ['POST', '/{id}/post', 'asset_depreciation_run.edit'],
    ]],
    'posting_journal' => [null, 'posting-journals', [
        ['GET', '', 'posting_journal'],
        ['POST', '/post', 'posting_journal.edit'],
    ]],
];

/**
 * Ratakan matriks menjadi dataset: [method, sub-path, izin, izin induk, model].
 *
 * @return array<string, array{0: string, 1: string, 2: string, 3: string, 4: ?string}>
 */
function purchasingFinanceDataset(array $matrix): array
{
    $rows = [];

    foreach ($matrix as $parent => [$model, $segment, $routes]) {
        foreach ($routes as [$method, $suffix, $permission]) {
            $label = "{$method} {$segment}{$suffix} ({$permission})";
            $rows[$label] = [$method, "{$segment}{$suffix}", $permission, $parent, $model];
        }
    }

    return $rows;
}

/**
 * Ganti {id} dengan id record nyata dari factory model induk.
 */
function resolvePurchasingFinanceUri(string $uri, ?string $model): string
{
    if (! str_contains($uri, '{id}') || $model === null) {
        return $uri;
    }

    $id = $model::factory()->create()->getKey();

    return str_replace('{id}', (string) $id, $uri);
}

/**
 * Kirim request ke route sesuai method.
 */
function callPurchasingFinanceRoute(string $method, string $uri)
{
    return match ($method) {
        'GET' => getJson("/api/{$uri}"),
        'PUT' => putJson("/api/{$uri}", []),
        'DELETE' => deleteJson("/api/{$uri}"),
        default => postJson("/api/{$uri}", []),
    };
}

dataset('purchasing-finance-routes', purchasingFinanceDataset($matrix));

/*
 * Subset aksi tulis: baris yang izinnya berbeda dari izin induk (GET dikecualikan,
 * karena GET hanya membutuhkan izin modul).
 */
dataset('purchasing-finance-routes-actions', array_filter(
    purchasingFinanceDataset($matrix),
    fn (array $row) => $row[2] !== $row[3],
));

/**
 * Jalankan request untuk satu baris matriks sebagai user dengan izin tertentu.
 *
 * @param  list<string>  $permissions
 */
function requestAs(array $permissions, string $method, string $uri, ?string $model)
{
    Sanctum::actingAs(createTestUserWithPermissions($permissions), ['*']);

    return callPurchasingFinanceRoute($method, resolvePurchasingFinanceUri($uri, $model));
}

test('route menolak user tanpa izin', function (...$row) {
    [$method, $uri, , , $model] = $row;

    requestAs([], $method, $uri, $model)->assertForbidden();
})->with('purchasing-finance-routes');

test('route menolak izin induk saja', function (...$row) {
    [$method, $uri, , $parent, $model] = $row;

    requestAs([$parent], $method, $uri, $model)->assertForbidden();
})->with('purchasing-finance-routes-actions');

test('route lolos dengan izin yang tepat', function (...$row) {
    [$method, $uri, $permission, $parent, $model] = $row;

    $status = requestAs([$parent, $permission], $method, $uri, $model)->getStatusCode();

    expect($status)->not->toBe(403);
})->with('purchasing-finance-routes');
