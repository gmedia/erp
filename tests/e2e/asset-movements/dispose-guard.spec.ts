import { expect, test } from '@playwright/test';

test('disposed asset is no longer offered for new movements', async ({
    page,
}) => {
    await page.goto('/asset-movements');
    await page.getByRole('button', { name: /Add/i }).click();

    const dialog = page.getByRole('dialog', { name: /movement/i });
    await expect(dialog).toBeVisible();

    await dialog.getByRole('combobox', { name: 'Asset' }).click();
    const picker = page
        .getByRole('dialog')
        .filter({ has: page.getByPlaceholder('Search...') });
    await expect(picker).toBeVisible({ timeout: 10000 });
    const assetLabel = (
        await picker.getByRole('button').nth(0).innerText()
    ).trim();
    await picker.getByRole('button').nth(0).click();

    await dialog
        .locator('button')
        .filter({ hasText: /Transfer|Assign|Return|Adjustment/i })
        .first()
        .click();
    await page
        .getByRole('option', { name: /^Dispose/i })
        .first()
        .click();
    await dialog
        .locator('input[name="reference"]')
        .fill(`DISPOSE-${Date.now()}`);

    const disposeResponse = page.waitForResponse(
        (response) =>
            response.url().includes('/api/asset-movements') &&
            response.request().method() === 'POST',
    );
    await dialog.getByRole('button', { name: /Record Movement/i }).click();
    expect((await disposeResponse).status()).toBeLessThan(400);

    await page.goto('/asset-movements');
    await page.getByRole('button', { name: /Add/i }).click();

    const secondDialog = page.getByRole('dialog', { name: /movement/i });
    await expect(secondDialog).toBeVisible();

    await secondDialog.getByRole('combobox', { name: 'Asset' }).click();
    const secondPicker = page
        .getByRole('dialog')
        .filter({ has: page.getByPlaceholder('Search...') });
    await expect(secondPicker).toBeVisible({ timeout: 10000 });
    await expect(
        secondPicker.getByRole('button', { name: assetLabel, exact: true }),
    ).toHaveCount(0);
});
