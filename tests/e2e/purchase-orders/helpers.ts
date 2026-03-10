import { expect, Page } from '@playwright/test';

export async function createPurchaseOrder(page: Page): Promise<string> {
    const createResult = await page.evaluate(async () => {
        const apiToken = localStorage.getItem('api_token') || '';

        const getFirstId = async (url: string): Promise<number> => {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Authorization': `Bearer ${apiToken}`,
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

        const response = await fetch('/api/purchase-orders', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Authorization': `Bearer ${apiToken}`,
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
                        quantity: 2,
                        unit_price: 1000,
                        discount_percent: 0,
                        tax_percent: 11,
                    },
                ],
            }),
        });

        const payload = await response.json().catch(() => ({}));
        return {
            ok: response.ok,
            poNumber: payload?.data?.po_number || '',
        };
    });

    expect(createResult.ok).toBeTruthy();
    expect(createResult.poNumber).not.toBe('');

    await page.reload();
    await page
        .waitForResponse((r) => r.url().includes('/api/purchase-orders') && r.status() < 400)
        .catch(() => null);

    return String(createResult.poNumber);
}

export async function searchPurchaseOrder(page: Page, identifier: string): Promise<void> {
    await page.getByPlaceholder(/search/i).fill(identifier);
    await page.keyboard.press('Enter');
    await page
        .waitForResponse(
            (r) => r.url().includes('/api/purchase-orders') && r.status() < 400,
        )
        .catch(() => null);
}

export async function editPurchaseOrder(
    page: Page,
    identifier: string,
    updates: Record<string, string> = {},
): Promise<void> {
    const updatedPoNumber = updates.po_number || `${identifier}-EDIT`;

    const updateResult = await page.evaluate(
        async ({ findBy, nextPoNumber }) => {
            const apiToken = localStorage.getItem('api_token') || '';

            const findResponse = await fetch(
                `/api/purchase-orders?search=${encodeURIComponent(findBy)}&per_page=1`,
                {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Authorization': `Bearer ${apiToken}`,
                    },
                },
            );
            const findPayload = await findResponse.json();
            const row = (findPayload.data || [])[0];
            if (!row?.id) {
                return { ok: false };
            }

            const updateResponse = await fetch(`/api/purchase-orders/${row.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Authorization': `Bearer ${apiToken}`,
                },
                body: JSON.stringify({ po_number: nextPoNumber }),
            });

            return { ok: updateResponse.ok };
        },
        { findBy: identifier, nextPoNumber: updatedPoNumber },
    );

    expect(updateResult.ok).toBeTruthy();

    await page.reload();
    await page
        .waitForResponse(
            (r) => r.url().includes('/api/purchase-orders') && r.status() < 400,
        )
        .catch(() => null);
}
