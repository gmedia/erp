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
            type GoodsReceiptDetailItem = {
                purchase_order_item_id?: number | string;
                product?: { id?: number | string } | null;
                unit?: { id?: number | string } | null;
                quantity_received?: number | string;
                quantity_accepted?: number | string;
                quantity_rejected?: number | string;
                unit_price?: number | string;
                notes?: string | null;
            };

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
                return { ok: false, step: 'find', status: findResponse.status, body: findPayload };
            }

            const detailResponse = await fetch(`/api/goods-receipts/${row.id}`, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    Authorization: `Bearer ${apiToken}`,
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
                gr_number: nextGrNumber,
                purchase_order_id: detail.purchase_order?.id,
                warehouse_id: detail.warehouse?.id,
                receipt_date: detail.receipt_date,
                supplier_delivery_note: detail.supplier_delivery_note ?? '',
                status: detail.status,
                notes: detail.notes ?? '',
                received_by: detail.received_by?.id ?? null,
                confirmed_by: detail.confirmed_by?.id ?? null,
                confirmed_at: detail.confirmed_at ?? null,
                items: (detail.items as GoodsReceiptDetailItem[] || []).map((item) => ({
                    purchase_order_item_id: item.purchase_order_item_id,
                    product_id: item.product?.id,
                    unit_id: item.unit?.id,
                    quantity_received: item.quantity_received,
                    quantity_accepted: item.quantity_accepted,
                    quantity_rejected: item.quantity_rejected,
                    unit_price: item.unit_price,
                    notes: item.notes ?? '',
                })),
            };

            const updateResponse = await fetch(`/api/goods-receipts/${row.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    Authorization: `Bearer ${apiToken}`,
                    'X-Requested-With': 'XMLHttpRequest',
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
        { findBy: identifier, nextGrNumber: updatedGrNumber },
    );

    expect(updateResult, JSON.stringify(updateResult)).toMatchObject({ ok: true });

    await page.reload();
    await page
        .waitForResponse(
            (r) => r.url().includes('/api/goods-receipts') && r.status() < 400,
        )
        .catch(() => null);
}
