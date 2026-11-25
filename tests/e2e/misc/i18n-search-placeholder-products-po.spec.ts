import { expect, test } from '@playwright/test';
import { login } from '../helpers';

const LOCALE_KEY = 'app_locale';

test.describe('i18n search placeholders (locale id) products and purchase orders', () => {
    test('products crud page search placeholder is Indonesian', async ({
        page,
    }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'id');
        }, LOCALE_KEY);

        await login(page);
        await page.goto('/products', { waitUntil: 'domcontentloaded' });

        await expect(
            page.getByPlaceholder('Cari kode, nama...'),
        ).toBeVisible({ timeout: 15000 });
        await expect(
            page.getByPlaceholder('Search code, name...'),
        ).toHaveCount(0);
    });

    test('purchase history report search placeholder is Indonesian', async ({
        page,
    }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'id');
        }, LOCALE_KEY);

        await login(page);
        await page.goto('/reports/purchase-history', {
            waitUntil: 'domcontentloaded',
        });

        await expect(
            page.getByPlaceholder(
                'Cari no. PO, pemasok, gudang, atau produk...',
            ),
        ).toBeVisible({ timeout: 15000 });
    });
});
