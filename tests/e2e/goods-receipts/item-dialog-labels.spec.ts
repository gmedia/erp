import { expect, test } from '@playwright/test';

import { login, searchAndWaitForApi } from '../helpers';
import { createGoodsReceipt } from './helpers';

test.describe('Goods Receipt item dialog labels', () => {
    test('label, placeholder, dan pesan validasi item memakai terjemahan', async ({
        page,
    }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
        const listed = page.waitForResponse(
            (r) =>
                r.url().includes('/api/goods-receipts') && r.status() < 400,
            { timeout: 30000 },
        );
        await page.goto('/goods-receipts');
        await listed;

        const receiptNumber = await createGoodsReceipt(page);
        await searchAndWaitForApi(
            page,
            page.getByPlaceholder(/search/i),
            receiptNumber,
            '/api/goods-receipts',
        );
        const row = page
            .locator('tbody tr')
            .filter({ hasText: receiptNumber })
            .first();
        await row.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: 'Edit' }).click();

        const formDialog = page.getByRole('dialog').first();
        await expect(formDialog).toBeVisible();
        await formDialog.getByRole('button', { name: 'Add Item' }).click();

        const itemDialog = page.getByRole('dialog').last();
        await expect(itemDialog.getByText('Quantity Received')).toBeVisible();
        await expect(itemDialog.getByText('Quantity Rejected')).toBeVisible();
        await expect(itemDialog.getByPlaceholder('Item notes')).toBeVisible();

        await itemDialog.getByRole('button', { name: /Save Item/i }).click();
        await expect(
            itemDialog.getByText('PO Item is required.'),
        ).toBeVisible();
    });
});
