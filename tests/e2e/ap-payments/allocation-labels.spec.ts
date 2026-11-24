import { expect, test } from '@playwright/test';

import { login, searchAndWaitForApi } from '../helpers';
import { createApPayment } from './helpers';

test.describe('AP Payment allocation dialog labels', () => {
    test('label, placeholder, dan pesan validasi alokasi memakai terjemahan', async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
        const listed = page.waitForResponse(
            (r) => r.url().includes('/api/ap-payments') && r.status() < 400,
            { timeout: 30000 },
        );
        await page.goto('/ap-payments');
        await listed;

        const paymentNumber = await createApPayment(page);
        await searchAndWaitForApi(page, page.getByPlaceholder(/search/i), paymentNumber, '/api/ap-payments');
        const row = page.locator('tbody tr').filter({ hasText: paymentNumber }).first();
        await row.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: 'Edit' }).click();

        const formDialog = page.getByRole('dialog').first();
        await expect(formDialog).toBeVisible();
        await formDialog.getByRole('button', { name: 'Add Item' }).click();

        const allocationDialog = page.getByRole('dialog').last();
        await expect(allocationDialog.getByText('Discount Taken')).toBeVisible();
        await expect(allocationDialog.getByPlaceholder('Allocation notes')).toBeVisible();

        await allocationDialog.getByRole('button', { name: 'Save Payment Allocation' }).click();
        await expect(allocationDialog.getByText('Bill is required.')).toBeVisible();
    });
});
