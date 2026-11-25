import { expect, test } from '@playwright/test';

import { login, searchAndWaitForApi } from '../helpers';
import { createSupplierReturn } from './helpers';

test.describe('Supplier Return item dialog labels', () => {
    test('label, placeholder, dan pesan validasi item memakai terjemahan', async ({
        page,
    }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
        const listed = page.waitForResponse(
            (r) =>
                r.url().includes('/api/supplier-returns') && r.status() < 400,
            { timeout: 30000 },
        );
        await page.goto('/supplier-returns');
        await listed;

        const returnNumber = await createSupplierReturn(page);
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

        const formDialog = page.getByRole('dialog').first();
        await expect(formDialog).toBeVisible();
        await formDialog.getByRole('button', { name: 'Add Item' }).click();

        // Daftar pilihan yang terbuka juga ber-role dialog, jadi dialog item dipilih lewat namanya.
        const itemDialog = page.getByRole('dialog', { name: 'Add Item' });
        await expect(itemDialog.getByText('Quantity Returned')).toBeVisible();
        await expect(itemDialog.getByPlaceholder('Item notes')).toBeVisible();

        await itemDialog.getByRole('button', { name: /Save Item/i }).click();
        await expect(
            itemDialog.getByText('GR item is required.'),
        ).toBeVisible();
    });
});
