import { expect, test } from '@playwright/test';

import { changeDateFieldValue, openModule } from '../helpers';
import { createStockAdjustment, searchStockAdjustment } from './helpers';

test.describe('Stock Adjustment date field', () => {
    test.beforeEach(async ({ page }) => {
        await openModule(page, '/stock-adjustments', '/api/stock-adjustments');
    });

    test('can change the adjustment date', async ({ page }) => {
        const adjustmentNumber = await createStockAdjustment(page);
        await searchStockAdjustment(page, adjustmentNumber);

        const row = page
            .locator('tbody tr')
            .filter({ hasText: adjustmentNumber })
            .first();
        await expect(row).toBeVisible();

        await row.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: 'Edit' }).click();

        const dialog = page.getByRole('dialog', {
            name: /Edit Stock Adjustment/i,
        });
        await expect(dialog).toBeVisible();

        const adjustmentDate = await changeDateFieldValue(
            page,
            dialog,
            'Adjustment Date',
        );

        const updateRequestPromise = page.waitForRequest(
            (request) =>
                request.url().includes('/api/stock-adjustments/') &&
                ['PUT', 'PATCH'].includes(request.method()),
        );
        const updateResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/stock-adjustments/') &&
                ['PUT', 'PATCH'].includes(response.request().method()) &&
                response.status() < 400,
        );

        await dialog.getByRole('button', { name: /Update/i }).click();

        const updateRequest = await updateRequestPromise;
        const payload = updateRequest.postDataJSON() as {
            adjustment_date?: string;
        };

        expect(payload.adjustment_date).toBe(adjustmentDate);

        await updateResponsePromise;
        await expect(dialog).not.toBeVisible();
    });
});
