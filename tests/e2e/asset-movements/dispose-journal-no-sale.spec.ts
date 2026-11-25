import { expect, test } from '@playwright/test';

test('pelepasan tanpa hasil penjualan tidak menampilkan akun penerimaan dan tersimpan', async ({
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
    await picker.getByRole('button').nth(0).click();

    await dialog.getByRole('combobox', { name: 'Movement Type' }).click();
    await page
        .getByRole('option', { name: /^Dispose/i })
        .first()
        .click();

    await expect(dialog.locator('input[name="sale_amount"]')).toBeVisible();
    await expect(dialog.locator('input[name="sale_amount"]')).toHaveValue('');
    await expect(
        dialog.getByRole('combobox', { name: /Receipt Account/i }),
    ).toHaveCount(0);

    const disposeResponse = page.waitForResponse(
        (response) =>
            response.url().includes('/api/asset-movements') &&
            response.request().method() === 'POST',
    );
    await dialog.getByRole('button', { name: /Record Movement/i }).click();
    expect((await disposeResponse).status()).toBeLessThan(400);
});
