import { expect, test } from '@playwright/test';

import {
    changeDateFieldValue,
    expectResponseOk,
    openModule,
} from '../helpers';
import { createInventoryStocktake, searchInventoryStocktake } from './helpers';

test.describe('Inventory Stocktake date field', () => {
    test.beforeEach(async ({ page }) => {
        await openModule(
            page,
            '/inventory-stocktakes',
            '/api/inventory-stocktakes',
        );
    });

    test('can change the stocktake date', async ({ page }) => {
        const stocktakeNumber = await createInventoryStocktake(page);
        await searchInventoryStocktake(page, stocktakeNumber);

        const row = page
            .locator('tbody tr')
            .filter({ hasText: stocktakeNumber })
            .first();
        await expect(row).toBeVisible();

        await row.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: 'Edit' }).click();

        const dialog = page.getByRole('dialog', {
            name: /Edit Inventory Stocktake/i,
        });
        await expect(dialog).toBeVisible();

        const stocktakeDate = await changeDateFieldValue(
            page,
            dialog,
            'Stocktake Date',
        );

        const updateRequestPromise = page.waitForRequest(
            (request) =>
                request.url().includes('/api/inventory-stocktakes/') &&
                ['PUT', 'PATCH'].includes(request.method()),
        );
        const updateResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/inventory-stocktakes/') &&
                ['PUT', 'PATCH'].includes(response.request().method()),
        );

        await dialog.getByRole('button', { name: /Update/i }).click();

        const updateRequest = await updateRequestPromise;
        const payload = updateRequest.postDataJSON() as {
            stocktake_date?: string;
        };

        expect(payload.stocktake_date).toBe(stocktakeDate);

        await expectResponseOk(await updateResponsePromise);
        await expect(dialog).not.toBeVisible();
    });
});
