import { expect, test } from '@playwright/test';
import { login } from '../helpers';

const LOCALE_KEY = 'app_locale';

test.describe('i18n column headers (locale id)', () => {
    test('journal entries (accounting) column headers are Indonesian', async ({
        page,
    }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'id');
        }, LOCALE_KEY);

        await login(page);
        await page.goto('/journal-entries', { waitUntil: 'domcontentloaded' });

        await expect(
            page.getByRole('button', { name: 'No. Jurnal' }),
        ).toBeVisible({ timeout: 15000 });
        await expect(
            page.getByRole('button', { name: 'Deskripsi' }),
        ).toBeVisible();
        await expect(
            page.getByRole('button', { name: 'Tanggal', exact: true }),
        ).toBeVisible();
        await expect(
            page.getByRole('button', { name: 'Entry Number' }),
        ).toHaveCount(0);
    });

    test('supplier bills (AP) column headers are Indonesian', async ({
        page,
    }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'id');
        }, LOCALE_KEY);

        await login(page);
        await page.goto('/supplier-bills', { waitUntil: 'domcontentloaded' });

        await expect(
            page.getByRole('button', { name: 'No. Tagihan' }),
        ).toBeVisible({ timeout: 15000 });
        await expect(
            page.getByRole('button', { name: 'Pemasok' }),
        ).toBeVisible();
        await expect(
            page.getByRole('button', { name: 'Sisa Tagihan' }),
        ).toBeVisible();
        await expect(
            page.getByRole('button', { name: 'Bill Number' }),
        ).toHaveCount(0);
    });
});

test.describe('i18n column headers (locale en)', () => {
    test('supplier bills (AP) column headers keep English labels', async ({
        page,
    }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'en');
        }, LOCALE_KEY);

        await login(page);
        await page.goto('/supplier-bills', { waitUntil: 'domcontentloaded' });

        await expect(
            page.getByRole('button', { name: 'Bill Number' }),
        ).toBeVisible({ timeout: 15000 });
        await expect(
            page.getByRole('button', { name: 'No. Tagihan' }),
        ).toHaveCount(0);
    });
});
