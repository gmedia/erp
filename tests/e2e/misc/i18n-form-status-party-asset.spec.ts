import { expect, test } from '@playwright/test';
import { login } from '../helpers';

const LOCALE_KEY = 'app_locale';

test.describe('i18n form status select (shared StatusSelectField)', () => {
    test('asset form status options are Indonesian in locale id', async ({
        page,
    }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'id');
        }, LOCALE_KEY);

        await login(page);
        await page.goto('/assets', { waitUntil: 'domcontentloaded' });

        await page
            .getByRole('button', { name: 'Tambah', exact: true })
            .first()
            .click();
        const dialog = page.getByRole('dialog').first();
        await expect(dialog).toBeVisible({ timeout: 15000 });

        await dialog
            .getByRole('combobox', { name: /Status/i })
            .first()
            .click();
        await expect(
            page.getByRole('option', { name: 'Aktif', exact: true }),
        ).toBeVisible();
        await expect(
            page.getByRole('option', { name: 'Active', exact: true }),
        ).toHaveCount(0);
        await page.keyboard.press('Escape');
    });

    test('asset form status options keep English labels in locale en', async ({
        page,
    }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'en');
        }, LOCALE_KEY);

        await login(page);
        await page.goto('/assets', { waitUntil: 'domcontentloaded' });

        await page
            .getByRole('button', { name: 'Add', exact: true })
            .first()
            .click();
        const dialog = page.getByRole('dialog').first();
        await expect(dialog).toBeVisible({ timeout: 15000 });

        await dialog
            .getByRole('combobox', { name: /Status/i })
            .first()
            .click();
        await expect(
            page.getByRole('option', { name: 'Active', exact: true }),
        ).toBeVisible();
        await expect(
            page.getByRole('option', { name: 'Aktif', exact: true }),
        ).toHaveCount(0);
    });
});
