import { expect, Page, test } from '@playwright/test';

import { approveApprovableAsDelegate, login, searchAndWaitForApi } from '../helpers';

interface SeedResult {
    receiptNumber: string;
    purchaseOrderId: number;
    purchaseOrderNumber: string;
    productName: string;
    otherPurchaseOrderNumber: string;
}

async function seedGoodsReceiptFixtures(page: Page): Promise<SeedResult> {
    return page.evaluate(async () => {
        const token = localStorage.getItem('api_token') || '';
        const headers = {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            Authorization: `Bearer ${token}`,
        };

        const firstId = async (url: string): Promise<number> => {
            const json = await (await fetch(url, { headers })).json();
            return Number((json.data || json)[0].id);
        };

        const [supplierId, warehouseId, productId, unitId] = await Promise.all([
            firstId('/api/suppliers?per_page=1'),
            firstId('/api/warehouses?per_page=1'),
            firstId('/api/products?search=Executive%20Office%20Desk&per_page=1'),
            firstId('/api/units?search=Piece&per_page=1'),
        ]);

        const today = new Date().toISOString().slice(0, 10);
        const poItems = [
            {
                product_id: productId,
                unit_id: unitId,
                quantity: 5,
                unit_price: 1000,
                discount_percent: 0,
                tax_percent: 11,
            },
        ];

        const createPo = async () => {
            const json = await (await fetch('/api/purchase-orders', {
                method: 'POST',
                headers,
                body: JSON.stringify({
                    supplier_id: supplierId,
                    warehouse_id: warehouseId,
                    order_date: today,
                    currency: 'IDR',
                    status: 'draft',
                    items: poItems,
                }),
            })).json();
            const id = Number(json?.data?.id);
            const transitions = await (await fetch(
                `/api/entity-states/purchase_order/${id}`,
                { headers },
            )).json();
            const submit = (transitions.data?.available_transitions ?? []).find(
                (t: { name: string }) => t.name === 'Submit',
            );
            await fetch(`/api/entity-states/purchase_order/${id}/transition`, {
                method: 'POST',
                headers,
                body: JSON.stringify({ transition_id: submit?.id }),
            });
            return {
                id,
                number: String(json?.data?.po_number ?? ''),
                itemId: Number(json?.data?.items?.[0]?.id),
            };
        };

        const po = await createPo();
        const otherPo = await createPo();

        const grResponse = await fetch('/api/goods-receipts', {
            method: 'POST',
            headers,
            body: JSON.stringify({
                purchase_order_id: po.id,
                warehouse_id: warehouseId,
                receipt_date: today,
                supplier_delivery_note: 'SJ-E2E-HEADER',
                status: 'draft',
                items: [
                    {
                        purchase_order_item_id: po.itemId,
                        product_id: productId,
                        unit_id: unitId,
                        quantity_received: 5,
                        quantity_accepted: 5,
                        quantity_rejected: 0,
                        unit_price: 1000,
                    },
                ],
            }),
        });
        const grPayload = await grResponse.json();
        const productJson = await (await fetch(`/api/products/${productId}`, { headers })).json();

        return {
            receiptNumber: String(grPayload?.data?.gr_number ?? ''),
            purchaseOrderId: po.id,
            purchaseOrderNumber: po.number,
            productName: String(productJson?.data?.name ?? ''),
            otherPurchaseOrderNumber: otherPo.number,
        };
    });
}

async function openGoodsReceiptEdit(page: Page, receiptNumber: string): Promise<void> {
    await searchAndWaitForApi(
        page,
        page.getByPlaceholder(/search/i),
        receiptNumber,
        '/api/goods-receipts',
    );
    const row = page.locator('tbody tr').filter({ hasText: receiptNumber }).first();
    await row.getByRole('button').last().click();
    await page.getByRole('menuitem', { name: 'Edit' }).click();
}

test.describe('Goods Receipt header selects', () => {
    test.describe.configure({ mode: 'serial' });

    let fixtures: SeedResult;

    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
        if (!fixtures) {
            fixtures = await seedGoodsReceiptFixtures(page);
            await approveApprovableAsDelegate(page, fixtures.purchaseOrderId, 'E2E header selects');
        }
        const listed = page.waitForResponse(
            (r) => r.url().includes('/api/goods-receipts') && r.status() < 400,
            { timeout: 30000 },
        );
        await page.goto('/goods-receipts');
        await listed;
    });

    test('edit dokumen lama menampilkan baris PO item yang terpilih', async ({ page }) => {
        expect(fixtures.receiptNumber).not.toBe('');

        await openGoodsReceiptEdit(page, fixtures.receiptNumber);
        const formDialog = page.getByRole('dialog').first();
        await expect(formDialog).toBeVisible();

        const purchaseOrderSelect = formDialog.getByRole('combobox', { name: 'Purchase Order' });
        await expect(purchaseOrderSelect).toContainText(fixtures.purchaseOrderNumber);
        await expect(formDialog.locator('tbody tr').first()).toContainText(fixtures.productName);
    });

    test('ganti PO di header mengosongkan baris item', async ({ page }) => {
        await openGoodsReceiptEdit(page, fixtures.receiptNumber);
        const formDialog = page.getByRole('dialog').first();
        await expect(formDialog.locator('tbody tr').first()).toContainText(fixtures.productName);

        const purchaseOrderSelect = formDialog.getByRole('combobox', { name: 'Purchase Order' });
        const listed = page.waitForResponse(
            (r) => r.url().includes('/api/purchase-orders') && r.ok(),
        );
        await purchaseOrderSelect.click();
        await listed;
        const searched = page.waitForResponse(
            (r) => r.url().includes('/api/purchase-orders') && r.ok(),
        );
        await page.getByPlaceholder('Search...').fill(fixtures.otherPurchaseOrderNumber);
        await searched;
        await page
            .getByRole('button', { name: new RegExp(fixtures.otherPurchaseOrderNumber) })
            .first()
            .click();

        await expect(formDialog.getByText('No items added yet.')).toBeVisible();
        await expect(formDialog.locator('tbody tr').filter({ hasText: fixtures.productName })).toHaveCount(0);
    });

    test('pencarian Purchase Order menyaring opsi sesuai nomor', async ({ page }) => {
        await page.getByRole('button', { name: /^Add$/i }).first().click();
        const formDialog = page.getByRole('dialog', { name: /Add New Goods Receipt/i });

        await formDialog.getByRole('combobox', { name: 'Purchase Order' }).click();
        const listed = page.waitForResponse(
            (r) => r.url().includes('/api/purchase-orders') && r.ok(),
        );
        await page.getByPlaceholder('Search...').fill(fixtures.purchaseOrderNumber);
        await listed;

        await expect(
            page.getByRole('button', { name: new RegExp(fixtures.purchaseOrderNumber) }).first(),
        ).toBeVisible();
        await expect(
            page.getByRole('button', { name: new RegExp(fixtures.otherPurchaseOrderNumber) }),
        ).toHaveCount(0);
    });
});
