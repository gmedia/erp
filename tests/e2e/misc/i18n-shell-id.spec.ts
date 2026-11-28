import { expect, test } from '@playwright/test';
import { APPLY_FILTERS_LABEL, login } from '../helpers';

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

    test('purchase orders status badges use Indonesian labels', async ({
        page,
    }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'id');
        }, LOCALE_KEY);

        await login(page);
        await page.goto('/purchase-orders', { waitUntil: 'domcontentloaded' });

        await expect(
            page.getByRole('columnheader', { name: 'Status' }),
        ).toBeVisible({ timeout: 15000 });
        await expect(
            page.getByText('Draf', { exact: true }).first(),
        ).toBeVisible();
        await expect(page.getByText('Draft', { exact: true })).toHaveCount(0);
    });
});

test.describe('i18n shell (locale en)', () => {
    test('purchase orders status badges keep English labels', async ({
        page,
    }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'en');
        }, LOCALE_KEY);

        await login(page);
        await page.goto('/purchase-orders', { waitUntil: 'domcontentloaded' });

        await expect(
            page.getByRole('columnheader', { name: 'Status' }),
        ).toBeVisible({ timeout: 15000 });
        await expect(
            page.getByText('Draft', { exact: true }).first(),
        ).toBeVisible();
        await expect(page.getByText('Draf', { exact: true })).toHaveCount(0);
    });

    test('purchase orders column headers keep English labels', async ({
        page,
    }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'en');
        }, LOCALE_KEY);

        await login(page);
        await page.goto('/purchase-orders', { waitUntil: 'domcontentloaded' });

        await expect(
            page.getByRole('button', { name: 'PO Number' }),
        ).toBeVisible({ timeout: 15000 });
        await expect(page.getByRole('button', { name: 'No. PO' })).toHaveCount(
            0,
        );
    });
});

test.describe('i18n page titles (locale id)', () => {
    test('purchase orders page title and description are Indonesian', async ({
        page,
    }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'id');
        }, LOCALE_KEY);

        await login(page);
        await page.goto('/purchase-orders', { waitUntil: 'domcontentloaded' });

        await expect(
            page.getByRole('heading', { level: 1, name: 'Pesanan Pembelian' }),
        ).toBeVisible({ timeout: 15000 });
        await expect(
            page.getByText(
                'Pesanan yang dikirim ke pemasok beserta progres penerimaan dan penagihan.',
            ),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', { level: 1, name: 'Purchase Orders' }),
        ).toHaveCount(0);
    });

    test('products page title and description are Indonesian', async ({
        page,
    }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'id');
        }, LOCALE_KEY);

        await login(page);
        await page.goto('/products', { waitUntil: 'domcontentloaded' });

        const breadcrumb = page.locator('nav[aria-label="breadcrumb"]').first();
        await expect(
            breadcrumb.getByText('Produk', { exact: true }),
        ).toBeVisible({
            timeout: 15000,
        });
        await expect(
            page.getByRole('heading', { level: 1, name: 'Produk' }),
        ).toBeVisible({ timeout: 15000 });
        await expect(
            page.getByText(
                'Produk, bahan, dan jasa beserta pengaturan harga dan stok.',
            ),
        ).toBeVisible();
    });
});

test.describe('i18n page titles (locale en)', () => {
    test('products page title and description keep English labels', async ({
        page,
    }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'en');
        }, LOCALE_KEY);

        await login(page);
        await page.goto('/products', { waitUntil: 'domcontentloaded' });

        await expect(
            page.getByRole('heading', {
                level: 1,
                name: 'Products',
                exact: true,
            }),
        ).toBeVisible({ timeout: 15000 });
        await expect(
            page.getByText(
                'Products, materials, and services with pricing and stock settings.',
            ),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', { level: 1, name: 'Produk' }),
        ).toHaveCount(0);
    });
});

test.describe('i18n page titles (locale id) journal and stock monitor', () => {
    test('journal entries page title is Indonesian', async ({ page }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'id');
        }, LOCALE_KEY);

        await login(page);
        await page.goto('/journal-entries', { waitUntil: 'domcontentloaded' });

        await expect(
            page.getByRole('heading', { level: 1, name: 'Jurnal Umum' }),
        ).toBeVisible({ timeout: 15000 });
        await expect(
            page.getByRole('heading', { level: 1, name: 'Journal Entries' }),
        ).toHaveCount(0);
    });

    test('stock monitor page title is Indonesian', async ({ page }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'id');
        }, LOCALE_KEY);

        await login(page);
        await page.goto('/stock-monitor', { waitUntil: 'domcontentloaded' });

        await expect(
            page.getByRole('heading', { level: 1, name: 'Pemantauan Stok' }),
        ).toBeVisible({ timeout: 15000 });
        await expect(
            page.getByRole('heading', { level: 1, name: 'Stock Monitor' }),
        ).toHaveCount(0);
    });
});

test.describe('i18n page titles (locale en) journal and stock monitor', () => {
    test('stock monitor page title keeps English', async ({ page }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'en');
        }, LOCALE_KEY);

        await login(page);
        await page.goto('/stock-monitor', { waitUntil: 'domcontentloaded' });

        await expect(
            page.getByRole('heading', { level: 1, name: 'Stock Monitor' }),
        ).toBeVisible({ timeout: 15000 });
        await expect(
            page.getByRole('heading', { level: 1, name: 'Pemantauan Stok' }),
        ).toHaveCount(0);
    });
});

test.describe('i18n filter inputs (locale id)', () => {
    test('stock monitor text filter applies its value in Indonesian', async ({
        page,
    }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'id');
        }, LOCALE_KEY);

        await login(page, undefined, undefined, { requireDashboard: false });
        await page.goto('/stock-monitor', { waitUntil: 'domcontentloaded' });

        await page.getByRole('button', { name: /filter/i }).click();
        const dialog = page.getByRole('dialog');
        const thresholdInput = dialog.getByPlaceholder('Contoh: 10');
        await expect(thresholdInput).toBeVisible({ timeout: 15000 });
        await thresholdInput.fill('1000');

        const filterRequest = page.waitForRequest(
            (r) =>
                r.url().includes('/stock-monitor') &&
                r.url().includes('low_stock_threshold=1000'),
        );
        await page
            .getByRole('button', { name: APPLY_FILTERS_LABEL.id })
            .click();
        await filterRequest;
    });
});

test.describe('i18n shell (locale id) columns', () => {
    test('purchase orders column headers use Indonesian labels', async ({
        page,
    }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'id');
        }, LOCALE_KEY);

        await login(page);
        await page.goto('/purchase-orders', { waitUntil: 'domcontentloaded' });

        await expect(page.getByRole('button', { name: 'No. PO' })).toBeVisible({
            timeout: 15000,
        });
        await expect(
            page.getByRole('button', { name: 'Pemasok' }),
        ).toBeVisible();
        await expect(
            page.getByRole('button', { name: 'PO Number' }),
        ).toHaveCount(0);
    });
});
