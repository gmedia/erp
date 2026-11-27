import { expect, test } from '@playwright/test';
import { login } from '../helpers';

const LOCALE_KEY = 'app_locale';

const cases = [
    {
        path: '/products',
        slug: 'products',
        labels: ['Kategori', 'Satuan', 'Cabang'],
        placeholders: ['Pilih kategori', 'Pilih satuan'],
    },
    {
        path: '/warehouses',
        slug: 'warehouses',
        labels: ['Cabang'],
        placeholders: ['Semua Cabang'],
    },
    {
        path: '/assets',
        slug: 'assets',
        labels: ['Kategori', 'Lokasi', 'Departemen', 'Karyawan', 'Pemasok', 'Cabang'],
        placeholders: ['Pilih kategori', 'Pilih lokasi', 'Pilih departemen'],
    },
];

test.describe('filter labels i18n (locale id)', () => {
    for (const c of cases) {
        test(`${c.slug} filter dialog labels and placeholders are Indonesian`, async ({
            page,
        }) => {
            await page.addInitScript((k) => {
                localStorage.setItem(k, 'id');
            }, LOCALE_KEY);

            await login(page);
            await page.goto(c.path, { waitUntil: 'domcontentloaded' });

            await page.getByTestId('filter-button').click();
            const dialog = page.getByRole('dialog');
            await expect(dialog).toBeVisible({ timeout: 15000 });

            for (const label of c.labels) {
                await expect(dialog.getByText(label, { exact: true }).first()).toBeVisible();
            }
            for (const placeholder of c.placeholders) {
                await expect(dialog.getByText(placeholder).first()).toBeVisible();
            }

            for (const english of ['Category', 'Unit', 'Branch', 'Select category', 'Select unit']) {
                await expect(dialog.getByText(english, { exact: true })).toHaveCount(0);
            }

            await page.screenshot({
                path: `/tmp/opencode/a599-filter-${c.slug}-id.png`,
                fullPage: false,
            });
        });
    }
});
