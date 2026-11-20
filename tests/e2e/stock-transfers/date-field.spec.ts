import { expect, test } from '@playwright/test';

import { changeDateFieldValue, openModule } from '../helpers';
import { createStockTransfer, searchStockTransfer } from './helpers';

test.describe('Stock Transfer date fields', () => {
    test.beforeEach(async ({ page }) => {
        await openModule(page, '/stock-transfers', '/api/stock-transfers');
    });

    test('can change transfer and expected arrival dates', async ({ page }) => {
        const transferNumber = await createStockTransfer(page);
        await searchStockTransfer(page, transferNumber);

        const row = page
            .locator('tbody tr')
            .filter({ hasText: transferNumber })
            .first();
        await expect(row).toBeVisible();

        await row.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: 'Edit' }).click();

        const dialog = page.getByRole('dialog', {
            name: /Edit Stock Transfer/i,
        });
        await expect(dialog).toBeVisible();

        const transferDate = await changeDateFieldValue(
            page,
            dialog,
            'Transfer Date',
        );
        const expectedArrivalDate = await changeDateFieldValue(
            page,
            dialog,
            'Expected Arrival Date',
        );

        const updateRequestPromise = page.waitForRequest(
            (request) =>
                request.url().includes('/api/stock-transfers/') &&
                ['PUT', 'PATCH'].includes(request.method()),
        );
        const updateResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/stock-transfers/') &&
                ['PUT', 'PATCH'].includes(response.request().method()) &&
                response.status() < 400,
        );

        await dialog.getByRole('button', { name: /Update/i }).click();

        const updateRequest = await updateRequestPromise;
        const payload = updateRequest.postDataJSON() as {
            transfer_date?: string;
            expected_arrival_date?: string;
        };

        expect(payload.transfer_date).toBe(transferDate);
        expect(payload.expected_arrival_date).toBe(expectedArrivalDate);

        await updateResponsePromise;
        await expect(dialog).not.toBeVisible();
    });
});
