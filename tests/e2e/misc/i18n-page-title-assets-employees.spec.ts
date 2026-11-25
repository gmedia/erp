import { expect, test } from '@playwright/test';
import { login } from '../helpers';

const LOCALE_KEY = 'app_locale';

test.describe('i18n page titles (locale id) assets and employees', () => {
    test('assets page title and breadcrumb are Indonesian', async ({
        page,
    }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'id');
        }, LOCALE_KEY);

        await login(page);
        await page.goto('/assets', { waitUntil: 'domcontentloaded' });

        await expect(
            page.getByRole('heading', { level: 1, name: 'Data Aset' }),
        ).toBeVisible({ timeout: 15000 });
        const breadcrumb = page.locator('nav[aria-label="breadcrumb"]').first();
        await expect(
            breadcrumb.getByText('Data Aset', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', { level: 1, name: 'Asset Data' }),
        ).toHaveCount(0);
        await expect(
            page.getByPlaceholder('Cari aset...'),
        ).toBeVisible();
    });

    test('employees page title is Indonesian', async ({ page }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'id');
        }, LOCALE_KEY);

        await login(page);
        await page.goto('/employees', { waitUntil: 'domcontentloaded' });

        await expect(
            page.getByRole('heading', { level: 1, name: 'Karyawan' }),
        ).toBeVisible({ timeout: 15000 });
        await expect(
            page.getByRole('heading', { level: 1, name: 'Employee' }),
        ).toHaveCount(0);
    });
});

test.describe('i18n page titles (locale id) ap aging report', () => {
    test('ap aging report title and breadcrumb are Indonesian', async ({
        page,
    }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'id');
        }, LOCALE_KEY);

        await login(page);
        await page.goto('/reports/ap-aging', { waitUntil: 'domcontentloaded' });

        await expect(
            page.getByRole('heading', {
                level: 1,
                name: 'Laporan Umur Hutang',
            }),
        ).toBeVisible({ timeout: 15000 });
        const breadcrumb = page.locator('nav[aria-label="breadcrumb"]').first();
        await expect(
            breadcrumb.getByText('Laporan Umur Hutang', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', { level: 1, name: 'AP Aging Report' }),
        ).toHaveCount(0);
    });
});

test.describe('i18n page titles (locale en) ap aging report', () => {
    test('ap aging report title keeps English', async ({ page }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'en');
        }, LOCALE_KEY);

        await login(page);
        await page.goto('/reports/ap-aging', { waitUntil: 'domcontentloaded' });

        await expect(
            page.getByRole('heading', { level: 1, name: 'AP Aging Report' }),
        ).toBeVisible({ timeout: 15000 });
        await expect(
            page.getByRole('heading', {
                level: 1,
                name: 'Laporan Umur Hutang',
            }),
        ).toHaveCount(0);
    });
});

test.describe('i18n page titles (locale en) assets and employees', () => {
    test('assets page title keeps English', async ({ page }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'en');
        }, LOCALE_KEY);

        await login(page);
        await page.goto('/assets', { waitUntil: 'domcontentloaded' });

        await expect(
            page.getByRole('heading', { level: 1, name: 'Assets' }),
        ).toBeVisible({ timeout: 15000 });
        await expect(
            page.getByRole('heading', { level: 1, name: 'Data Aset' }),
        ).toHaveCount(0);
    });

    test('employees page title keeps English', async ({ page }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'en');
        }, LOCALE_KEY);

        await login(page);
        await page.goto('/employees', { waitUntil: 'domcontentloaded' });

        await expect(
            page.getByRole('heading', { level: 1, name: 'Employees' }),
        ).toBeVisible({ timeout: 15000 });
        await expect(
            page.getByRole('heading', { level: 1, name: 'Karyawan' }),
        ).toHaveCount(0);
    });
});
