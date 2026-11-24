import { expect, Page, test } from '@playwright/test';

import { login, searchAndWaitForApi } from '../helpers';

interface SeedResult {
    returnNumber: string;
    purchaseOrderNumber: string;
    otherPurchaseOrderNumber: string;
    productName: string;
}

async function seedSupplierReturnFixtures(page: Page): Promise<SeedResult> {
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
            firstId('/api/products?search=Meja%20Kerja%20Eksekutif&per_page=1'),
            firstId('/api/units?search=Buah&per_page=1'),
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
            const json = await (
                await fetch('/api/purchase-orders', {
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
                })
            ).json();
            return {
                id: Number(json?.data?.id),
                number: String(json?.data?.po_number ?? ''),
                itemId: Number(json?.data?.items?.[0]?.id),
            };
        };

        const po = await createPo();
        const otherPo = await createPo();

        const grJson = await (
            await fetch('/api/goods-receipts', {
                method: 'POST',
                headers,
                body: JSON.stringify({
                    purchase_order_id: po.id,
                    warehouse_id: warehouseId,
                    receipt_date: today,
                    supplier_delivery_note: 'SJ-E2E-SR-HEADER',
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
            })
        ).json();
        const goodsReceiptId = Number(grJson?.data?.id);
        const goodsReceiptItemId = Number(grJson?.data?.items?.[0]?.id);

        const srJson = await (
            await fetch('/api/supplier-returns', {
                method: 'POST',
                headers,
                body: JSON.stringify({
                    purchase_order_id: po.id,
                    goods_receipt_id: goodsReceiptId,
                    supplier_id: supplierId,
                    warehouse_id: warehouseId,
                    return_date: today,
                    reason: 'defective',
                    status: 'draft',
                    items: [
                        {
                            goods_receipt_item_id: goodsReceiptItemId,
                            product_id: productId,
                            unit_id: unitId,
                            quantity_returned: 2,
                            unit_price: 1000,
                        },
                    ],
                }),
            })
        ).json();

        const productJson = await (
            await fetch(`/api/products/${productId}`, { headers })
        ).json();

        return {
            returnNumber: String(srJson?.data?.return_number ?? ''),
            purchaseOrderNumber: po.number,
            otherPurchaseOrderNumber: otherPo.number,
            productName: String(productJson?.data?.name ?? ''),
        };
    });
}

async function openSupplierReturnEdit(
    page: Page,
    returnNumber: string,
): Promise<void> {
    await searchAndWaitForApi(
        page,
        page.getByPlaceholder(/search/i),
        returnNumber,
        '/api/supplier-returns',
    );
    const row = page
        .locator('tbody tr')
        .filter({ hasText: returnNumber })
        .first();
    await row.getByRole('button').last().click();
    await page.getByRole('menuitem', { name: 'Edit' }).click();
}

test.describe('Supplier Return header selects', () => {
    test.describe.configure({ mode: 'serial' });

    let fixtures: SeedResult;

    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
        fixtures ??= await seedSupplierReturnFixtures(page);
        const listed = page.waitForResponse(
            (r) =>
                r.url().includes('/api/supplier-returns') && r.status() < 400,
            { timeout: 30000 },
        );
        await page.goto('/supplier-returns');
        await listed;
    });

    test('edit dokumen lama menampilkan baris item yang terpilih', async ({
        page,
    }) => {
        expect(fixtures.returnNumber).not.toBe('');

        await openSupplierReturnEdit(page, fixtures.returnNumber);
        const formDialog = page.getByRole('dialog').first();
        await expect(formDialog).toBeVisible();

        await expect(
            formDialog.getByRole('combobox', { name: 'Purchase Order' }),
        ).toContainText(fixtures.purchaseOrderNumber);
        await expect(formDialog.locator('tbody tr').first()).toContainText(
            fixtures.productName,
        );
    });

    test('ganti PO di header mengosongkan baris item', async ({ page }) => {
        await openSupplierReturnEdit(page, fixtures.returnNumber);
        const formDialog = page.getByRole('dialog').first();
        await expect(formDialog.locator('tbody tr').first()).toContainText(
            fixtures.productName,
        );

        await formDialog
            .getByRole('combobox', { name: 'Purchase Order' })
            .click();
        const listed = page.waitForResponse(
            (r) => r.url().includes('/api/purchase-orders') && r.ok(),
        );
        await page
            .getByPlaceholder('Search...')
            .fill(fixtures.otherPurchaseOrderNumber);
        await listed;
        await page
            .getByRole('button', {
                name: new RegExp(fixtures.otherPurchaseOrderNumber),
            })
            .first()
            .click();

        await expect(formDialog.getByText('No items added yet.')).toBeVisible();
    });

    test('pencarian Purchase Order menyaring opsi sesuai nomor', async ({
        page,
    }) => {
        await page.getByRole('button', { name: /^Add$/i }).first().click();
        const formDialog = page.getByRole('dialog', {
            name: /Add New Supplier Return/i,
        });

        await formDialog
            .getByRole('combobox', { name: 'Purchase Order' })
            .click();
        const listed = page.waitForResponse(
            (r) => r.url().includes('/api/purchase-orders') && r.ok(),
        );
        await page
            .getByPlaceholder('Search...')
            .fill(fixtures.purchaseOrderNumber);
        await listed;

        await expect(
            page
                .getByRole('button', {
                    name: new RegExp(fixtures.purchaseOrderNumber),
                })
                .first(),
        ).toBeVisible();
        await expect(
            page.getByRole('button', {
                name: new RegExp(fixtures.otherPurchaseOrderNumber),
            }),
        ).toHaveCount(0);
    });
});
