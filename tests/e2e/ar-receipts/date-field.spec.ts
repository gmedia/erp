import { expect, test } from '@playwright/test';

import { changeDateFieldValue, openModule } from '../helpers';
import { createArReceipt, searchArReceipt } from './helpers';

test.describe('AR Receipt date field', () => {
    test.beforeEach(async ({ page }) => {
        await openModule(page, '/ar-receipts', '/api/ar-receipts');
    });

    test('can change the receipt date', async ({ page }) => {
        const receiptNumber = await createArReceipt(page);
        await searchArReceipt(page, receiptNumber);

        const row = page
            .locator('tbody tr')
            .filter({ hasText: receiptNumber })
            .first();
        await expect(row).toBeVisible();

        await row.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: 'Edit' }).click();

        const dialog = page.getByRole('dialog', {
            name: /Edit AR Receipt/i,
        });
        await expect(dialog).toBeVisible();

        const receiptDate = await changeDateFieldValue(
            page,
            dialog,
            'Receipt Date',
        );

        const updateRequestPromise = page.waitForRequest(
            (request) =>
                request.url().includes('/api/ar-receipts/') &&
                ['PUT', 'PATCH'].includes(request.method()),
        );
        const updateResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/ar-receipts/') &&
                ['PUT', 'PATCH'].includes(response.request().method()) &&
                response.status() < 400,
        );

        await dialog.getByRole('button', { name: /Update/i }).click();

        const updateRequest = await updateRequestPromise;
        const payload = updateRequest.postDataJSON() as {
            receipt_date?: string;
        };

        expect(payload.receipt_date).toBe(receiptDate);

        await updateResponsePromise;
        await expect(dialog).not.toBeVisible();
    });
});
