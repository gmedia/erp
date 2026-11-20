import { expect, test } from '@playwright/test';

import { changeDateFieldValue, openModule } from '../helpers';
import { createSupplierReturn, searchSupplierReturn } from './helpers';

test.describe('Supplier Return date field', () => {
    test.beforeEach(async ({ page }) => {
        await openModule(page, '/supplier-returns', '/api/supplier-returns');
    });

    test('can change the return date', async ({ page }) => {
        const returnNumber = await createSupplierReturn(page);
        await searchSupplierReturn(page, returnNumber);

        const row = page
            .locator('tbody tr')
            .filter({ hasText: returnNumber })
            .first();
        await expect(row).toBeVisible();

        await row.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: 'Edit' }).click();

        const dialog = page.getByRole('dialog', {
            name: /Edit Supplier Return/i,
        });
        await expect(dialog).toBeVisible();

        const returnDate = await changeDateFieldValue(
            page,
            dialog,
            'Return Date',
        );

        const updateRequestPromise = page.waitForRequest(
            (request) =>
                request.url().includes('/api/supplier-returns/') &&
                ['PUT', 'PATCH'].includes(request.method()),
        );
        const updateResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/supplier-returns/') &&
                ['PUT', 'PATCH'].includes(response.request().method()) &&
                response.status() < 400,
        );

        await dialog.getByRole('button', { name: /Update/i }).click();

        const updateRequest = await updateRequestPromise;
        const payload = updateRequest.postDataJSON() as {
            return_date?: string;
        };

        expect(payload.return_date).toBe(returnDate);

        await updateResponsePromise;
        await expect(dialog).not.toBeVisible();
    });
});
