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

test('profil aset disposed tanpa jurnal menampilkan teks tanpa jurnal dan tanpa tautan', async ({
    page,
}) => {
    await page.goto('/assets');
    const adminToken = await page.evaluate(() =>
        localStorage.getItem('api_token'),
    );
    expect(adminToken, 'api_token harus ada di localStorage').toBeTruthy();

    const listResponse = await page.request.get(
        '/api/assets?search=FA-000007',
        {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${adminToken}`,
            },
        },
    );
    expect(listResponse.status()).toBeLessThan(400);
    const list = (await listResponse.json()) as {
        data: { ulid: string; asset_code: string }[];
    };
    const asset = list.data.find((a) => a.asset_code === 'FA-000007');
    expect(asset, 'aset FA-000007 hasil seed harus ada').toBeTruthy();

    const profileResponse = page.waitForResponse(
        (response) =>
            response.url().includes(`/api/assets/${asset!.ulid}/profile`) &&
            response.request().method() === 'GET',
    );
    await page.goto(`/assets/${asset!.ulid}`);
    expect((await profileResponse).status()).toBeLessThan(400);

    await page.getByRole('tab', { name: /Movements/ }).click();
    await expect(page.getByText('No sale proceeds').first()).toBeVisible();
    await expect(page.getByTestId('disposal-no-journal').first()).toHaveText(
        /no journal/i,
    );
    await expect(page.getByTestId('disposal-journal-link')).toHaveCount(0);
});
