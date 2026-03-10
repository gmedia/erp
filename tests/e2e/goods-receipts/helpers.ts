import { expect, Page } from '@playwright/test';

export async function createGoodsReceipt(page: Page): Promise<string> {
    const createResult = await page.evaluate(async () => {
        const apiToken = localStorage.getItem('api_token') || '';

        const getFirstId = async (url: string): Promise<number> => {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Authorization: `Bearer ${apiToken}`,
                },
            });
            const json = await response.json();
            const rows = json.data || json;
            return Number(rows[0].id);
        };

        const [supplierId, warehouseId, productId, unitId] = await Promise.all([
            getFirstId('/api/suppliers?per_page=1'),
            getFirstId('/api/warehouses?per_page=1'),
            getFirstId('/api/products?per_page=1'),
            getFirstId('/api/units?per_page=1'),
        ]);

        const poResponse = await fetch('/api/purchase-orders', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                Authorization: `Bearer ${apiToken}`,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                supplier_id: supplierId,
                warehouse_id: warehouseId,
                order_date: new Date().toISOString().slice(0, 10),
                currency: 'IDR',
                status: 'draft',
                items: [
                    {
                        product_id: productId,
                        unit_id: unitId,
                        quantity: 5,
                        unit_price: 1000,
                        discount_percent: 0,
                        tax_percent: 11,
                    },
                ],
            }),
        });
        const poPayload = await poResponse.json().catch(() => ({}));
        const purchaseOrderId = poPayload?.data?.id;
        const purchaseOrderItemId = poPayload?.data?.items?.[0]?.id;

        const grResponse = await fetch('/api/goods-receipts', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                Authorization: `Bearer ${apiToken}`,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                purchase_order_id: purchaseOrderId,
                warehouse_id: warehouseId,
                receipt_date: new Date().toISOString().slice(0, 10),
                supplier_delivery_note: 'SJ-E2E-001',
                status: 'draft',
                items: [
                    {
                        purchase_order_item_id: purchaseOrderItemId,
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

        const payload = await grResponse.json().catch(() => ({}));
        return {
            ok: grResponse.ok,
            grNumber: payload?.data?.gr_number || '',
        };
    });

    expect(createResult.ok).toBeTruthy();
    expect(createResult.grNumber).not.toBe('');

    await page.reload();
    await page
        .waitForResponse((r) => r.url().includes('/api/goods-receipts') && r.status() < 400)
        .catch(() => null);

    return String(createResult.grNumber);
}

export async function searchGoodsReceipt(page: Page, identifier: string): Promise<void> {
    await page.getByPlaceholder(/search/i).fill(identifier);
    await page.keyboard.press('Enter');
    await page
        .waitForResponse(
            (r) => r.url().includes('/api/goods-receipts') && r.status() < 400,
        )
        .catch(() => null);
}

export async function editGoodsReceipt(
    page: Page,
    identifier: string,
    updates: Record<string, string> = {},
): Promise<void> {
    const updatedGrNumber = updates.gr_number || `${identifier}-EDIT`;

    const updateResult = await page.evaluate(
        async ({ findBy, nextGrNumber }) => {
            const apiToken = localStorage.getItem('api_token') || '';

            const findResponse = await fetch(
                `/api/goods-receipts?search=${encodeURIComponent(findBy)}&per_page=1`,
                {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Authorization: `Bearer ${apiToken}`,
                    },
                },
            );
            const findPayload = await findResponse.json();
            const row = (findPayload.data || [])[0];
            if (!row?.id) {
                return { ok: false };
            }

            const updateResponse = await fetch(`/api/goods-receipts/${row.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    Authorization: `Bearer ${apiToken}`,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ gr_number: nextGrNumber }),
            });

            return { ok: updateResponse.ok };
        },
        { findBy: identifier, nextGrNumber: updatedGrNumber },
    );

    expect(updateResult.ok).toBeTruthy();

    await page.reload();
    await page
        .waitForResponse(
            (r) => r.url().includes('/api/goods-receipts') && r.status() < 400,
        )
        .catch(() => null);
}
