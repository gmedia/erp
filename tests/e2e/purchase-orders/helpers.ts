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
            type PurchaseOrderDetailItem = {
                purchase_request_item_id?: number | string;
                product?: { id?: number | string } | null;
                unit?: { id?: number | string } | null;
                quantity?: number | string;
                unit_price?: number | string;
                discount_percent?: number | string;
                tax_percent?: number | string;
                notes?: string | null;
            };

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
                return { ok: false, step: 'find', status: findResponse.status, body: findPayload };
            }

            const detailResponse = await fetch(`/api/purchase-orders/${row.id}`, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Authorization': `Bearer ${apiToken}`,
                },
            });
            const detailPayload = await detailResponse.json().catch(() => ({}));
            const detail = detailPayload?.data;

            if (!detail?.id) {
                return {
                    ok: false,
                    step: 'show',
                    status: detailResponse.status,
                    body: detailPayload,
                };
            }

            const payload = {
                po_number: nextPoNumber,
                supplier_id: detail.supplier?.id,
                warehouse_id: detail.warehouse?.id,
                order_date: detail.order_date,
                expected_delivery_date: detail.expected_delivery_date ?? null,
                payment_terms: detail.payment_terms ?? '',
                currency: detail.currency,
                status: detail.status,
                notes: detail.notes ?? '',
                shipping_address: detail.shipping_address ?? '',
                approved_by: detail.approved_by?.id ?? null,
                approved_at: detail.approved_at ?? null,
                items: (detail.items as PurchaseOrderDetailItem[] || []).map((item) => ({
                    purchase_request_item_id: item.purchase_request_item_id ?? null,
                    product_id: item.product?.id,
                    unit_id: item.unit?.id,
                    quantity: item.quantity,
                    unit_price: item.unit_price,
                    discount_percent: item.discount_percent ?? 0,
                    tax_percent: item.tax_percent ?? 0,
                    notes: item.notes ?? '',
                })),
            };

            const updateResponse = await fetch(`/api/purchase-orders/${row.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Authorization': `Bearer ${apiToken}`,
                },
                body: JSON.stringify(payload),
            });

            const updatePayload = await updateResponse.json().catch(() => ({}));

            return {
                ok: updateResponse.ok,
                step: 'update',
                status: updateResponse.status,
                body: updatePayload,
            };
        },
        { findBy: identifier, nextPoNumber: updatedPoNumber },
    );

    expect(updateResult, JSON.stringify(updateResult)).toMatchObject({ ok: true });

    await page.reload();
    await page
        .waitForResponse(
            (r) => r.url().includes('/api/purchase-orders') && r.status() < 400,
        )
        .catch(() => null);
}
