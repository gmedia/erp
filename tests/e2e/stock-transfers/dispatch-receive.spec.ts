import { expect, test, type Page } from '@playwright/test';

import { ensureAppOrigin, expectResponseOk, openModule } from '../helpers';
import { approvePurchaseOrderFixture } from '../purchase-orders/helpers';
import { createStockTransfer, searchStockTransfer } from './helpers';

/**
 * Issue #360 — alur dispatch → receive lewat UI. Stok total (gudang asal + tujuan)
 * harus tetap sama setelah transfer diterima penuh.
 */

async function readTransferDetail(page: Page, transferNumber: string): Promise<{
    id: number;
    fromWarehouseId: number;
    toWarehouseId: number;
    productId: number;
    quantity: number;
    status: string;
}> {
    return page.evaluate(async (number) => {
        const apiToken = localStorage.getItem('api_token') || '';
        const listResponse = await fetch(
            `/api/stock-transfers?search=${encodeURIComponent(number)}&per_page=5`,
            { headers: { Accept: 'application/json', Authorization: `Bearer ${apiToken}` } },
        );
        const list = await listResponse.json();
        const row = (list.data || []).find(
            (item: { transfer_number: string }) => item.transfer_number === number,
        );
        const detailResponse = await fetch(`/api/stock-transfers/${row.id}`, {
            headers: { Accept: 'application/json', Authorization: `Bearer ${apiToken}` },
        });
        const detail = (await detailResponse.json()).data;

        return {
            id: Number(detail.id),
            fromWarehouseId: Number(detail.from_warehouse?.id),
            toWarehouseId: Number(detail.to_warehouse?.id),
            productId: Number(detail.items?.[0]?.product?.id),
            quantity: Number(detail.items?.[0]?.quantity),
            status: String(detail.status),
        };
    }, transferNumber);
}

/** Total saldo produk di gudang: saldo terakhir per gudang (balance_after, moved_at, id). */
async function warehouseBalance(page: Page, warehouseId: number, productId: number): Promise<number> {
    return page.evaluate(
        async ({ warehouseId, productId }) => {
            const apiToken = localStorage.getItem('api_token') || '';
            const response = await fetch(
                `/api/stock-movements?warehouse_id=${warehouseId}&product_id=${productId}&per_page=100`,
                { headers: { Accept: 'application/json', Authorization: `Bearer ${apiToken}` } },
            );
            const json = await response.json();
            const rows = (json.data || []) as Array<{ quantity_in: string; quantity_out: string }>;

            return rows.reduce(
                (net, row) => net + Number(row.quantity_in) - Number(row.quantity_out),
                0,
            );
        },
        { warehouseId, productId },
    );
}

async function changeStatusViaUi(page: Page, transferNumber: string, status: string): Promise<void> {
    const row = page
        .locator('tbody tr')
        .filter({ hasText: transferNumber })
        .first();

    // Search memicu request API hanya bila nilainya berubah; status kedua memakai baris yang sudah terfilter.
    if (!(await row.isVisible().catch(() => false))) {
        await searchStockTransfer(page, transferNumber);
    }
    await expect(row).toBeVisible();

    await row.getByRole('button').last().click();
    await page.getByRole('menuitem', { name: 'Edit' }).click();

    const dialog = page.getByRole('dialog', { name: /Edit Stock Transfer/i });
    await expect(dialog).toBeVisible();
    // Item hanya dimuat dari detail; tunggu sampai baris item tampil, bukan menunggu request.
    await expect(dialog.locator('tbody tr').filter({ hasNotText: 'No items added yet.' }).first()).toBeVisible({ timeout: 15000 });

    const statusTrigger = dialog.getByRole('combobox', { name: /Status/i });
    await statusTrigger.click();
    await page.getByRole('option', { name: status, exact: true }).click();

    const updateResponse = page.waitForResponse(
        (response) =>
            response.url().includes('/api/stock-transfers/') &&
            ['PUT', 'PATCH'].includes(response.request().method()),
    );

    await dialog.getByRole('button', { name: /Update/i }).click();
    await expectResponseOk(await updateResponse);
    await expect(dialog).toBeHidden();
}

// Stok disiapkan lewat API (PO → GR confirmed). Jumlahnya harus >= qty transfer,
// karena dispatch sekarang menolak stok negatif (#356 baris 6).
async function seedStockAtSource(page: Page, warehouseId: number, productId: number, quantity: number): Promise<void> {
    const draft = await page.evaluate(
        async ({ warehouseId, productId, quantity }) => {
            const apiToken = localStorage.getItem('api_token') || '';
            const headers = {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                Authorization: `Bearer ${apiToken}`,
                'X-Requested-With': 'XMLHttpRequest',
            };
            const getFirst = async (url: string) => {
                const json = await (await fetch(url, { headers })).json();
                return (json.data || json)[0];
            };

            const supplier = await getFirst('/api/suppliers?per_page=1');
            const unit = await getFirst('/api/units?per_page=1');
            const today = new Date().toISOString().slice(0, 10);

            const po = await (
                await fetch('/api/purchase-orders', {
                    method: 'POST',
                    headers,
                    body: JSON.stringify({
                        supplier_id: supplier.id,
                        warehouse_id: warehouseId,
                        order_date: today,
                        currency: 'IDR',
                        status: 'draft',
                        items: [{ product_id: productId, unit_id: unit.id, quantity, unit_price: 1000, discount_percent: 0, tax_percent: 0 }],
                    }),
                })
            ).json();

            return {
                poId: Number(po.data.id),
                poNumber: String(po.data.po_number),
                poItemId: Number(po.data.items[0].id),
                unitId: Number(unit.id),
                today,
            };
        },
        { warehouseId, productId, quantity },
    );

    approvePurchaseOrderFixture(draft.poNumber);

    const gr = await page.evaluate(
        async ({ draft, warehouseId, productId, quantity }) => {
            const apiToken = localStorage.getItem('api_token') || '';
            const response = await fetch('/api/goods-receipts', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    Authorization: `Bearer ${apiToken}`,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    purchase_order_id: draft.poId,
                    warehouse_id: warehouseId,
                    receipt_date: draft.today,
                    supplier_delivery_note: 'SJ-E2E-360',
                    status: 'confirmed',
                    items: [{
                        purchase_order_item_id: draft.poItemId,
                        product_id: productId,
                        unit_id: draft.unitId,
                        quantity_received: quantity,
                        quantity_accepted: quantity,
                        quantity_rejected: 0,
                        unit_price: 1000,
                    }],
                }),
            });

            return response.ok;
        },
        { draft, warehouseId, productId, quantity },
    );

    expect(gr).toBeTruthy();
}

test.describe('Stock Transfer dispatch → receive (Issue #360)', () => {
    test.beforeEach(async ({ page }) => {
        await openModule(page, '/stock-transfers', '/api/stock-transfers');
    });

    test('transfer dispatch lalu receive lewat UI: stok total gudang asal + tujuan tetap', async ({ page }) => {
        await ensureAppOrigin(page);

        const transferNumber = await createStockTransfer(page);
        const { id, fromWarehouseId, toWarehouseId, productId, quantity } = await readTransferDetail(page, transferNumber);
        await seedStockAtSource(page, fromWarehouseId, productId, quantity);

        const totalBefore =
            (await warehouseBalance(page, fromWarehouseId, productId)) +
            (await warehouseBalance(page, toWarehouseId, productId));

        await changeStatusViaUi(page, transferNumber, 'In Transit');
        await changeStatusViaUi(page, transferNumber, 'Received');

        const after = await readTransferDetail(page, transferNumber);
        expect(after.status).toBe('received');

        const totalAfter =
            (await warehouseBalance(page, fromWarehouseId, productId)) +
            (await warehouseBalance(page, toWarehouseId, productId));

        // Stok total tidak boleh berubah: yang keluar dari asal harus masuk ke tujuan.
        expect(totalAfter).toBeCloseTo(totalBefore, 2);
        expect(id).toBeGreaterThan(0);
    });

    test('receive lewat form tanpa mengisi Qty Received → diterima penuh, tujuan bertambah qty kirim', async ({ page }) => {
        await ensureAppOrigin(page);

        const transferNumber = await createStockTransfer(page);
        const { toWarehouseId, fromWarehouseId, productId, quantity } = await readTransferDetail(page, transferNumber);
        await seedStockAtSource(page, fromWarehouseId, productId, quantity);

        const destinationBefore = await warehouseBalance(page, toWarehouseId, productId);

        await changeStatusViaUi(page, transferNumber, 'In Transit');

        const receivedRequest = page.waitForRequest(
            (request) =>
                request.url().includes('/api/stock-transfers/') &&
                request.method() === 'PUT' &&
                String(request.postData() ?? '').includes('"received"'),
        );
        await changeStatusViaUi(page, transferNumber, 'Received');
        const payload = JSON.parse((await receivedRequest).postData() ?? '{}');
        expect(payload.items?.[0]?.quantity_received ?? null).toBeNull();

        const destinationAfter = await warehouseBalance(page, toWarehouseId, productId);
        expect(destinationAfter - destinationBefore).toBeCloseTo(quantity, 2);
    });
});
