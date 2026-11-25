import { expect, test } from '@playwright/test';

import { changeDateFieldValue, expectResponseOk, openModule } from '../helpers';
import { createFiscalYear, searchFiscalYear } from './helpers';

test.describe('Fiscal Year date fields', () => {
    test.beforeEach(async ({ page }) => {
        await openModule(page, '/fiscal-years', '/api/fiscal-years');
    });

    test('can change the start date', async ({ page }) => {
        const name = await createFiscalYear(page);
        await searchFiscalYear(page, name);

        const row = page.locator('tbody tr').filter({ hasText: name }).first();
        await expect(row).toBeVisible();

        await row.getByRole('button', { name: /Actions/i }).click();
        await page.getByRole('menuitem', { name: /Edit/i }).click();

        const dialog = page.getByRole('dialog', {
            name: /Edit Fiscal Year/i,
        });
        await expect(dialog).toBeVisible();

        const startDate = await changeDateFieldValue(
            page,
            dialog,
            'Start Date',
        );

        const updateRequestPromise = page.waitForRequest(
            (request) =>
                request.url().includes('/api/fiscal-years/') &&
                ['PUT', 'PATCH'].includes(request.method()),
        );
        const updateResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/fiscal-years/') &&
                ['PUT', 'PATCH'].includes(response.request().method()),
        );

        await dialog.getByRole('button', { name: /Update/i }).click();

        const updateRequest = await updateRequestPromise;
        const payload = updateRequest.postDataJSON() as {
            start_date?: string;
        };

        expect(payload.start_date).toBe(startDate);

        await expectResponseOk(await updateResponsePromise);
        await expect(dialog).not.toBeVisible();
    });
});
