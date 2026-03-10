import { expect, Page } from '@playwright/test';

export async function createPurchaseOrderReportData(page: Page): Promise<string> {
    const createResult = await page.evaluate(async () => {
        const csrf =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') || '';
        const xsrfCookie = document.cookie
            .split('; ')
            .find((row) => row.startsWith('XSRF-TOKEN='));
        const xsrfToken = xsrfCookie
            ? decodeURIComponent(xsrfCookie.split('=')[1])
            : '';

        const getFirstId = async (url: string): Promise<number> => {
            const apiToken = localStorage.getItem('api_token') || '';
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

        const apiToken = localStorage.getItem('api_token') || '';
        const response = await fetch('/api/purchase-orders', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                Authorization: `Bearer ${apiToken}`,
            },
            body: JSON.stringify({
                supplier_id: supplierId,
                warehouse_id: warehouseId,
                order_date: new Date().toISOString().slice(0, 10),
                currency: 'IDR',
                status: 'confirmed',
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

        const payload = await response.json().catch(() => ({}));
        return {
            ok: response.ok,
            poNumber: payload?.data?.po_number || '',
        };
    });

    expect(createResult.ok).toBeTruthy();
    expect(createResult.poNumber).not.toBe('');

    return String(createResult.poNumber);
}

export async function waitForPurchaseOrderStatusReportResponse(page: Page): Promise<void> {
    await page
        .waitForResponse(
            (response) =>
                response.url().includes('/api/reports/purchase-order-status') &&
                response.request().headers()['accept']?.includes(
                    'application/json',
                ) &&
                response.status() < 400,
        )
        .catch(() => null);
}

export async function openPurchaseOrderStatusReport(page: Page): Promise<void> {
    await page.goto('/reports/purchase-order-status');
    await page.waitForURL('**/reports/purchase-order-status', { timeout: 15000 });
    await waitForPurchaseOrderStatusReportResponse(page);

    await expect(page.locator('table')).toBeVisible();
    await expect(page.locator('tbody tr').first()).toBeVisible();
}
