import { expect, test } from '@playwright/test';
import { mkdirSync } from 'node:fs';
import { homedir } from 'node:os';
import { join } from 'node:path';
import { login } from '../helpers';

const LOCALE_KEY = 'app_locale';
const SHOT_DIR = join(homedir(), '.erp-agent', 'shots-599');

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
        labels: [
            'Kategori',
            'Lokasi',
            'Departemen',
            'Karyawan',
            'Pemasok',
            'Cabang',
        ],
        placeholders: ['Pilih kategori', 'Pilih lokasi', 'Pilih departemen'],
    },
    {
        path: '/customers',
        slug: 'customers',
        labels: ['Cabang', 'Kategori', 'Status'],
        placeholders: ['Pilih cabang', 'Pilih kategori', 'Semua Status'],
    },
    {
        path: '/purchase-orders',
        slug: 'purchase-orders',
        labels: [
            'Status',
            'Mata Uang',
            'Tanggal Pesan Dari',
            'Tanggal Pesan Sampai',
        ],
        placeholders: ['Pilih status', 'Pilih mata uang'],
    },
    {
        path: '/reports/ap-aging',
        slug: 'reports-ap-aging',
        labels: ['Pemasok', 'Cabang', 'Status'],
        placeholders: ['Pilih pemasok', 'Pilih cabang'],
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
                await expect(
                    dialog.getByText(label, { exact: true }).first(),
                ).toBeVisible();
            }
            for (const placeholder of c.placeholders) {
                await expect(
                    dialog.getByText(placeholder).first(),
                ).toBeVisible();
            }

            for (const english of [
                'Category',
                'Unit',
                'Branch',
                'Select category',
                'Select unit',
            ]) {
                await expect(
                    dialog.getByText(english, { exact: true }),
                ).toHaveCount(0);
            }

            mkdirSync(SHOT_DIR, { recursive: true });
            await page.screenshot({
                path: join(SHOT_DIR, `filter-${c.slug}-id.png`),
                fullPage: false,
            });
        });
    }
});
