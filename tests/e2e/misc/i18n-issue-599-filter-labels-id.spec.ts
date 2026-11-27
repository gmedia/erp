import { expect, test } from '@playwright/test';
import { login } from '../helpers';

const LOCALE_KEY = 'app_locale';
const SHOT_DIR = '/tmp/opencode/issue-599-shots';

const cases = [
    {
        slug: 'ap-aging',
        path: '/reports/ap-aging',
        labels: ['Pemasok', 'Cabang', 'Status', 'Per Tanggal'],
    },
    {
        slug: 'ar-aging',
        path: '/reports/ar-aging',
        labels: ['Pelanggan', 'Cabang', 'Status'],
    },
    {
        slug: 'trial-balance-detailed',
        path: '/reports/trial-balance-detailed',
        labels: ['Tahun Fiskal', 'Bulan'],
    },
    {
        slug: 'budget-variance',
        path: '/reports/budget-variance',
        labels: ['Anggaran', 'Status', 'Jenis Akun'],
    },
];

test.describe('issue 599 filter dialog labels (locale id)', () => {
    for (const c of cases) {
        test(`filter dialog ${c.slug} shows Indonesian labels`, async ({
            page,
        }) => {
            await page.setViewportSize({ width: 1440, height: 900 });
            await page.addInitScript((k) => {
                localStorage.setItem(k, 'id');
            }, LOCALE_KEY);

            await login(page);
            await page.goto(c.path, { waitUntil: 'domcontentloaded' });

            await page
                .getByRole('button', { name: /filter/i })
                .first()
                .click();
            const dialog = page.getByRole('dialog');
            await expect(dialog).toBeVisible({ timeout: 15000 });

            for (const label of c.labels) {
                await expect(
                    dialog.getByText(label, { exact: true }).first(),
                ).toBeVisible();
            }
            await expect(
                dialog.getByText('Supplier', { exact: true }),
            ).toHaveCount(0);

            await dialog.screenshot({
                path: `${SHOT_DIR}/${c.slug}-id.png`,
            });
        });
    }
});
