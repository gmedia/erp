import { expect, test, type Page } from '@playwright/test';

import { login, reloadAndWaitForApi, searchAndWaitForApi } from '../helpers';
import { createSupplierReturn } from './helpers';

async function confirmSupplierReturnViaApi(
    page: Page,
    returnNumber: string,
): Promise<number> {
    const result = await page.evaluate(async (number) => {
        const apiToken = localStorage.getItem('api_token') || '';
        const headers = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            Authorization: `Bearer ${apiToken}`,
        };

        const findResponse = await fetch(
            `/api/supplier-returns?search=${encodeURIComponent(number)}&per_page=1`,
            { headers },
        );
        const found = (await findResponse.json()).data?.[0];
        if (!found?.id) {
            return { ok: false, id: 0 };
        }

        const detail = (
            await (
                await fetch(`/api/supplier-returns/${found.id}`, { headers })
            ).json()
        ).data;

        const updateResponse = await fetch(
            `/api/supplier-returns/${found.id}`,
            {
                method: 'PUT',
                headers: { ...headers, 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    purchase_order_id: detail.purchase_order?.id,
                    goods_receipt_id: detail.goods_receipt?.id,
                    supplier_id: detail.supplier?.id,
                    warehouse_id: detail.warehouse?.id,
                    return_date: detail.return_date,
                    reason: detail.reason,
                    status: 'confirmed',
                    items: (detail.items ?? []).map(
                        (item: {
                            goods_receipt_item_id: number;
                            product?: { id: number } | null;
                            unit?: { id: number } | null;
                            quantity_returned: string;
                            unit_price: string;
                        }) => ({
                            goods_receipt_item_id: item.goods_receipt_item_id,
                            product_id: item.product?.id,
                            unit_id: item.unit?.id,
                            quantity_returned: item.quantity_returned,
                            unit_price: item.unit_price,
                        }),
                    ),
                }),
            },
        );

        return { ok: updateResponse.ok, id: Number(found.id) };
    }, returnNumber);

    expect(result.ok, 'confirm supplier return via API').toBeTruthy();

    return result.id;
}

async function countReversalMovements(
    page: Page,
    supplierReturnId: number,
): Promise<number> {
    return page.evaluate(async (id) => {
        const apiToken = localStorage.getItem('api_token') || '';
        const response = await fetch('/api/stock-movements?per_page=100', {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                Authorization: `Bearer ${apiToken}`,
            },
        });
        const rows: Array<{
            movement_type: string;
            reference_type: string | null;
            reference_id: number | null;
        }> = (await response.json()).data ?? [];

        return rows.filter(
            (row) =>
                row.movement_type === 'supplier_return_reversal' &&
                row.reference_id === id,
        ).length;
    }, supplierReturnId);
}

test.describe('Supplier Return cancel (confirmed)', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
        await page.goto('/supplier-returns');
        await page.waitForResponse(
            (response) =>
                response.url().includes('/api/supplier-returns') &&
                response.status() < 400,
        );
    });

    test('cancel confirmed supplier return from detail dialog reverses stock and status', async ({
        page,
    }) => {
        const returnNumber = await createSupplierReturn(page);
        const supplierReturnId = await confirmSupplierReturnViaApi(
            page,
            returnNumber,
        );

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
        await expect(row).toBeVisible();
        await row.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: 'View' }).click();

        const detail = page.getByRole('dialog', {
            name: /Supplier Return Details/i,
        });
        await expect(detail).toBeVisible();
        await detail.getByTestId('open-cancel-supplier-return').click();

        const dialog = page.getByRole('alertdialog');
        await expect(dialog).toBeVisible();

        const confirmButton = dialog.getByTestId(
            'confirm-cancel-supplier-return',
        );
        await confirmButton.click();
        await expect(
            dialog.getByTestId('cancel-supplier-return-error'),
        ).toBeVisible();

        await dialog
            .getByTestId('cancel-supplier-return-reason')
            .fill('Salah kirim barang, dibatalkan oleh gudang');
        await confirmButton.click();

        await expect(dialog).toBeHidden();

        await reloadAndWaitForApi(page, '/api/supplier-returns');
        await searchAndWaitForApi(
            page,
            page.getByPlaceholder(/search/i),
            returnNumber,
            '/api/supplier-returns',
        );

        await expect(
            page.getByText('Cancelled', { exact: true }).first(),
        ).toBeVisible();

        expect(
            await countReversalMovements(page, supplierReturnId),
        ).toBeGreaterThan(0);
    });
});
