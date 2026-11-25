import { expect, test } from '@playwright/test';

import { changeDateFieldValue, expectResponseOk, openModule } from '../helpers';
import { getReconcilableRow } from './helpers';

test.describe('Bank Reconciliation date field', () => {
    test.beforeEach(async ({ page }) => {
        await openModule(
            page,
            '/bank-reconciliations',
            '/api/bank-reconciliations',
        );
    });

    test('can change the period start date', async ({ page }) => {
        const row = getReconcilableRow(page);
        await expect(row).toBeVisible();

        await row.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: 'Edit' }).click();

        const dialog = page.getByRole('dialog', {
            name: /Edit Bank Reconciliation/i,
        });
        await expect(dialog).toBeVisible();

        const expectedDate = await changeDateFieldValue(
            page,
            dialog,
            'Period Start',
        );

        const updateRequestPromise = page.waitForRequest(
            (request) =>
                request.url().includes('/api/bank-reconciliations/') &&
                ['PUT', 'PATCH'].includes(request.method()),
        );
        const updateResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/bank-reconciliations/') &&
                ['PUT', 'PATCH'].includes(response.request().method()),
        );

        await dialog.getByRole('button', { name: /Update/i }).click();

        const updateRequest = await updateRequestPromise;
        const payload = updateRequest.postDataJSON() as {
            period_start?: string;
        };

        expect(payload.period_start).toBe(expectedDate);

        await expectResponseOk(await updateResponsePromise);
        await expect(dialog).not.toBeVisible();
    });
});
