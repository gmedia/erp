import { expect, test } from '@playwright/test';
import { expectResponseOk } from '../helpers';

import { createAsset, searchAsset } from './helpers';

test('can change the purchase date of an existing asset', async ({ page }) => {
    const assetName = await createAsset(page, {
        name: `Asset Purchase Date ${Date.now()}`,
    });

    await searchAsset(page, assetName);
    const row = page.locator('tbody tr').filter({ hasText: assetName }).first();
    await expect(row).toBeVisible();

    await row.getByRole('button', { name: /Actions/i }).click();
    await page.getByRole('menuitem', { name: /Edit/i }).click();

    const dialog = page.getByRole('dialog', { name: /Edit Asset/i });
    await expect(dialog).toBeVisible();

    const selectedDay = new Date().getDate() === 15 ? 16 : 15;
    const targetDate = new Date();
    targetDate.setDate(selectedDay);
    const expectedPurchaseDate = `${targetDate.getFullYear()}-${String(
        targetDate.getMonth() + 1,
    ).padStart(2, '0')}-${String(selectedDay).padStart(2, '0')}`;

    const purchaseDateTrigger = dialog
        .getByText('Purchase Date', { exact: true })
        .locator('xpath=..')
        .locator('button');

    await purchaseDateTrigger.click();
    await page
        .locator('button[data-day]')
        .filter({ hasText: new RegExp(`^${selectedDay}$`) })
        .first()
        .click();

    const updateRequestPromise = page.waitForRequest(
        (request) =>
            request.url().includes('/api/assets') &&
            ['PUT', 'PATCH'].includes(request.method()),
    );
    const updateResponsePromise = page.waitForResponse(
        (response) =>
            response.url().includes('/api/assets') &&
            ['PUT', 'PATCH'].includes(response.request().method()),
    );

    await dialog.getByRole('button', { name: /Save|Update/i }).click();

    const updateRequest = await updateRequestPromise;
    const payload = updateRequest.postDataJSON() as {
        purchase_date?: string;
    };

    expect(payload.purchase_date).toBe(expectedPurchaseDate);

    await expectResponseOk(await updateResponsePromise);
    await expect(dialog).not.toBeVisible();
});
