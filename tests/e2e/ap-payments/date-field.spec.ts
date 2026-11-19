import { expect, test } from '@playwright/test';

import { changeDateFieldValue, openModule } from '../helpers';
import { createApPayment, searchApPayment } from './helpers';

test.describe('AP Payment date field', () => {
    test.beforeEach(async ({ page }) => {
        await openModule(page, '/ap-payments', '/api/ap-payments');
    });

    test('can change the payment date', async ({ page }) => {
        const paymentNumber = await createApPayment(page);
        await searchApPayment(page, paymentNumber);

        const row = page
            .locator('tbody tr')
            .filter({ hasText: paymentNumber })
            .first();
        await expect(row).toBeVisible();

        await row.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: 'Edit' }).click();

        const dialog = page.getByRole('dialog', {
            name: /Edit AP Payment/i,
        });
        await expect(dialog).toBeVisible();

        const expectedDate = await changeDateFieldValue(
            page,
            dialog,
            'Payment Date',
        );

        const updateRequestPromise = page.waitForRequest(
            (request) =>
                request.url().includes('/api/ap-payments/') &&
                ['PUT', 'PATCH'].includes(request.method()),
        );
        const updateResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/ap-payments/') &&
                ['PUT', 'PATCH'].includes(response.request().method()) &&
                response.status() < 400,
        );

        await dialog.getByRole('button', { name: /Update/i }).click();

        const updateRequest = await updateRequestPromise;
        const payload = updateRequest.postDataJSON() as {
            payment_date?: string;
        };

        expect(payload.payment_date).toBe(expectedDate);

        await updateResponsePromise;
        await expect(dialog).not.toBeVisible();
    });
});
