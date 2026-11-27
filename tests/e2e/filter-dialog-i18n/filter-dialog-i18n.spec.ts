import { expect, test, type Page } from '@playwright/test';
import { login } from '../helpers';

async function openFilterDialog(page: Page, path: string): Promise<void> {
    await page.addInitScript(() => {
        localStorage.setItem('app_locale', 'id');
    });
    await login(page, undefined, undefined, { requireDashboard: false });
    await page.goto(path);
    await expect(page.locator('table')).toBeVisible();
    await expect(page.getByText('Loading...')).not.toBeVisible();
    await page.getByRole('button', { name: 'Filter', exact: true }).click();
}

test.describe('Filter dialog locale id', () => {
    test('products filter dialog shows indonesian title, description, and actions', async ({
        page,
    }) => {
        await openFilterDialog(page, '/products');

        const dialog = page.getByRole('dialog').filter({ hasText: 'Filter' });
        await expect(dialog).toBeVisible();
        await expect(
            dialog.getByText('Terapkan filter untuk menyaring hasil'),
        ).toBeVisible();
        await expect(
            dialog.getByRole('button', { name: 'Atur ulang' }),
        ).toBeVisible();
        await expect(
            dialog.getByRole('button', { name: 'Hapus semua' }),
        ).toBeVisible();
        await expect(
            dialog.getByRole('button', { name: 'Terapkan filter' }),
        ).toBeVisible();
        await expect(dialog.getByText('Apply filters to refine')).toHaveCount(
            0,
        );
    });

    test('security logs filter dialog uses single date labels and indonesian placeholders', async ({
        page,
    }) => {
        await openFilterDialog(page, '/security-logs');

        const dialog = page.getByRole('dialog').filter({ hasText: 'Filter' });
        await expect(dialog).toBeVisible();
        await expect(dialog.getByText('Pilih kejadian')).toBeVisible();
        await expect(
            dialog.getByPlaceholder('mis. 192.168.1.10'),
        ).toBeVisible();
        await expect(dialog.getByText('Tanggal mulai', { exact: true })).toHaveCount(1);
        await expect(dialog.getByText('Tanggal selesai', { exact: true })).toHaveCount(1);
    });
});
