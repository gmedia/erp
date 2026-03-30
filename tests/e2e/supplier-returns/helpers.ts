import { expect, Page } from '@playwright/test';

export async function createSupplierReturn(page: Page): Promise<string> {
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
        const grPayload = await grResponse.json().catch(() => ({}));
        const goodsReceiptId = grPayload?.data?.id;
        const goodsReceiptItemId = grPayload?.data?.items?.[0]?.id;

        const srResponse = await fetch('/api/supplier-returns', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                Authorization: `Bearer ${apiToken}`,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                purchase_order_id: purchaseOrderId,
                goods_receipt_id: goodsReceiptId,
                supplier_id: supplierId,
                warehouse_id: warehouseId,
                return_date: new Date().toISOString().slice(0, 10),
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
        });

        const payload = await srResponse.json().catch(() => ({}));
        return {
            ok: srResponse.ok,
            returnNumber: payload?.data?.return_number || '',
        };
    });

    expect(createResult.ok).toBeTruthy();
    expect(createResult.returnNumber).not.toBe('');

    const responsePromise = page.waitForResponse(
        (r) => r.url().includes('/api/supplier-returns') && r.status() < 400,
        { timeout: 10000 }
    ).catch(() => null);
    
    await page.reload();
    await responsePromise;

    return String(createResult.returnNumber);
}

export async function searchSupplierReturn(page: Page, identifier: string): Promise<void> {
    await page.getByPlaceholder(/search/i).fill(identifier);
    
    const responsePromise = page.waitForResponse(
        (r) => r.url().includes('/api/supplier-returns') && r.status() < 400,
        { timeout: 10000 }
    ).catch(() => null);
    
    await page.keyboard.press('Enter');
    await responsePromise;
}

export async function editSupplierReturn(
    page: Page,
    identifier: string,
    updates: Record<string, string> = {},
): Promise<void> {
    const updatedReturnNumber = updates.return_number || `${identifier}-EDIT`;

    const updateResult = await page.evaluate(
        async ({ findBy, nextReturnNumber }) => {
            type SupplierReturnDetailItem = {
                goods_receipt_item_id?: number | string;
                product?: { id?: number | string } | null;
                unit?: { id?: number | string } | null;
                quantity_returned?: number | string;
                unit_price?: number | string;
                notes?: string | null;
            };

            const apiToken = localStorage.getItem('api_token') || '';

            const findResponse = await fetch(
                `/api/supplier-returns?search=${encodeURIComponent(findBy)}&per_page=1`,
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
                return {
                    ok: false,
                    step: 'find',
                    status: findResponse.status,
                    body: findPayload,
                };
            }

            const detailResponse = await fetch(`/api/supplier-returns/${row.id}`, {
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
                return_number: nextReturnNumber,
                purchase_order_id: detail.purchase_order?.id,
                goods_receipt_id: detail.goods_receipt?.id,
                supplier_id: detail.supplier?.id,
                warehouse_id: detail.warehouse?.id,
                return_date: detail.return_date,
                reason: detail.reason,
                status: detail.status,
                notes: detail.notes ?? '',
                items: (detail.items as SupplierReturnDetailItem[] || []).map((item) => ({
                    goods_receipt_item_id: item.goods_receipt_item_id,
                    product_id: item.product?.id,
                    unit_id: item.unit?.id,
                    quantity_returned: item.quantity_returned,
                    unit_price: item.unit_price,
                    notes: item.notes ?? '',
                })),
            };

            const updateResponse = await fetch(`/api/supplier-returns/${row.id}`, {
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
        { findBy: identifier, nextReturnNumber: updatedReturnNumber },
    );

    expect(updateResult, JSON.stringify(updateResult)).toMatchObject({ ok: true });

    const editPromise = page.waitForResponse(
        (r) => r.url().includes('/api/supplier-returns') && r.status() < 400,
        { timeout: 10000 }
    ).catch(() => null);
    
    await page.reload();
    await editPromise;
}
