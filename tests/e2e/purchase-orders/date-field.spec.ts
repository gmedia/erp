import { expect, test } from '@playwright/test';

import {
    changeDateFieldValue,
    expectResponseOk,
    openModule,
} from '../helpers';
import { createPurchaseOrder, searchPurchaseOrder } from './helpers';

test.describe('Purchase Order date field', () => {
    test.beforeEach(async ({ page }) => {
        await openModule(page, '/purchase-orders', '/api/purchase-orders');
    });

    test('can change the order date', async ({ page }) => {
        const poNumber = await createPurchaseOrder(page);
        await searchPurchaseOrder(page, poNumber);

        const row = page
            .locator('tbody tr')
            .filter({ hasText: poNumber })
            .first();
        await expect(row).toBeVisible();

        await row.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: 'Edit' }).click();

        const dialog = page.getByRole('dialog', {
            name: /Edit Purchase Order/i,
        });
        await expect(dialog).toBeVisible();

        const expectedDate = await changeDateFieldValue(
            page,
            dialog,
            'Order Date',
        );

        const updateRequestPromise = page.waitForRequest(
            (request) =>
                request.url().includes('/api/purchase-orders/') &&
                ['PUT', 'PATCH'].includes(request.method()),
        );
        const updateResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/purchase-orders/') &&
                ['PUT', 'PATCH'].includes(response.request().method()),
        );

        await dialog.getByRole('button', { name: /Update/i }).click();

        const updateRequest = await updateRequestPromise;
        const payload = updateRequest.postDataJSON() as {
            order_date?: string;
        };

        expect(payload.order_date).toBe(expectedDate);

        await expectResponseOk(await updateResponsePromise);
        await expect(dialog).not.toBeVisible();
    });
});
