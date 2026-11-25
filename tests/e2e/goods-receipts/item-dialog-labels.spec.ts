import { expect, test } from '@playwright/test';

import { expectResponseOk, login, searchAndWaitForApi } from '../helpers';
import { createGoodsReceipt } from './helpers';

test.describe('Goods Receipt item dialog labels', () => {
    test('label, placeholder, dan pesan validasi item memakai terjemahan', async ({
        page,
    }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
        const listed = page.waitForResponse(
            (r) => r.url().includes('/api/goods-receipts'),
            { timeout: 30000 },
        );
        await page.goto('/goods-receipts');
        await expectResponseOk(await listed);

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

        // Daftar pilihan yang terbuka juga ber-role dialog, jadi dialog item dipilih lewat namanya.
        const itemDialog = page.getByRole('dialog', { name: 'Add Item' });
        await expect(itemDialog.getByText('Quantity Received')).toBeVisible();
        await expect(itemDialog.getByText('Quantity Rejected')).toBeVisible();
        await expect(itemDialog.getByPlaceholder('Item notes')).toBeVisible();

        const poItemPicker = itemDialog.getByRole('combobox', {
            name: 'PO Item',
        });
        await poItemPicker.click();
        const firstOption = page.locator('ul[aria-busy] button').first();
        await expect(firstOption).toContainText(/remaining|sisa/);
        await poItemPicker.click();

        await itemDialog.getByRole('button', { name: /Save Item/i }).click();
        await expect(
            itemDialog.getByText('PO Item is required.'),
        ).toBeVisible();
    });
});
