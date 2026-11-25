import { expect, test } from '@playwright/test';
import { login } from '../helpers';

const LOCALE_KEY = 'app_locale';

test.describe('i18n shell (locale id)', () => {
    test('purchase orders shell shows breadcrumb, toolbar and pagination in Indonesian', async ({
        page,
    }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'id');
        }, LOCALE_KEY);

        await login(page);
        await page.goto('/purchase-orders', { waitUntil: 'domcontentloaded' });

        const breadcrumb = page.locator('nav[aria-label="breadcrumb"]').first();
        await expect(breadcrumb.getByText('Persediaan')).toBeVisible({
            timeout: 15000,
        });
        await expect(breadcrumb.getByText('Pesanan Pembelian')).toBeVisible();

        await expect(page.getByTestId('filter-button')).toHaveAttribute(
            'aria-label',
            'Filter',
        );
        await expect(page.getByTestId('add-button')).toHaveAttribute(
            'aria-label',
            'Tambah',
        );
        await expect(page.getByTestId('export-button')).toHaveAttribute(
            'aria-label',
            'Ekspor',
        );
        await expect(page.getByRole('button', { name: 'Kolom' })).toBeVisible();

        await expect(page.getByText('Baris per halaman').first()).toBeVisible();
        await expect(
            page.getByText(/Menampilkan .* sampai .* dari .* data/).first(),
        ).toBeVisible();
        await expect(
            page.getByRole('link', { name: 'Go to previous page' }),
        ).toBeVisible();
        await expect(
            page.getByRole('link', { name: 'Go to next page' }),
        ).toBeVisible();

        await expect(page.getByText('Rows per page')).toHaveCount(0);
        await expect(page.getByText('Showing ')).toHaveCount(0);
    });
});
