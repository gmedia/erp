import { expect, test } from '@playwright/test';
import { login } from '../helpers';

const LOCALE_KEY = 'app_locale';

async function switchLocale(page: import('@playwright/test').Page, label: string) {
    await page.getByRole('button', { name: /English|Indonesia/ }).first().click();
    await page.getByRole('menuitem', { name: label }).click();
}

test.describe('language switcher', () => {
    test('switches the app to Indonesian and back to English, persisting across reload', async ({
        page,
    }) => {
        await login(page);

        await switchLocale(page, 'Indonesia');
        await page.waitForLoadState('domcontentloaded');
        await expect(page.getByText('Dasbor').first()).toBeVisible({ timeout: 15000 });
        expect(await page.evaluate((k) => localStorage.getItem(k), LOCALE_KEY)).toBe('id');

        await page.reload({ waitUntil: 'domcontentloaded' });
        await expect(page.getByText('Dasbor').first()).toBeVisible({ timeout: 15000 });

        await switchLocale(page, 'English');
        await page.waitForLoadState('domcontentloaded');
        await expect(page.getByText('Dashboard').first()).toBeVisible({ timeout: 15000 });
        expect(await page.evaluate((k) => localStorage.getItem(k), LOCALE_KEY)).toBe('en');
    });

});

test.describe('language switcher on guest pages', () => {
    test.use({ storageState: { cookies: [], origins: [] } });

    test('login page follows the stored Indonesian locale', async ({ page }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'id');
        }, LOCALE_KEY);

        await page.goto('/login', { waitUntil: 'domcontentloaded' });

        await expect(page.getByText('Masuk ke akun Anda').first()).toBeVisible({ timeout: 15000 });
        await expect(page.getByRole('button', { name: 'Masuk', exact: true })).toBeVisible();
        await expect(page.getByLabel('Alamat email')).toBeVisible();
    });
});
