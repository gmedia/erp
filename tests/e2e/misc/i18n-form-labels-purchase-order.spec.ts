import { expect, test } from '@playwright/test';
import { login } from '../helpers';

const LOCALE_KEY = 'app_locale';

async function openAddPurchaseOrderDialog(
    page: import('@playwright/test').Page,
    addLabel: RegExp,
    dialogTitle: RegExp,
): Promise<void> {
    const listResponse = page.waitForResponse((response) =>
        response.url().includes('/api/purchase-orders'),
    );
    await page.goto('/purchase-orders', { waitUntil: 'domcontentloaded' });
    await listResponse;
    await page.getByRole('button', { name: addLabel }).first().click();
    await expect(
        page.getByRole('dialog', { name: dialogTitle }),
    ).toBeVisible({ timeout: 15000 });
}

test.describe('i18n form labels purchase order', () => {
    test('add purchase order dialog labels are Indonesian (locale id)', async ({
        page,
    }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'id');
        }, LOCALE_KEY);

        await login(page);
        await openAddPurchaseOrderDialog(
            page,
            /^Tambah$/i,
            /Tambah Pesanan Pembelian Baru/i,
        );

        const dialog = page.getByRole('dialog', {
            name: /Tambah Pesanan Pembelian Baru/i,
        });
        await expect(dialog.getByText('No. PO', { exact: true })).toBeVisible();
        await expect(dialog.getByText('Pemasok', { exact: true })).toBeVisible();
        await expect(dialog.getByText('Gudang', { exact: true })).toBeVisible();
        await expect(
            dialog.getByText('Tanggal Pesanan', { exact: true }),
        ).toBeVisible();
        await expect(
            dialog.getByPlaceholder('Dibuat otomatis jika kosong'),
        ).toBeVisible();
        await expect(dialog.getByText('PO Number', { exact: true })).toHaveCount(
            0,
        );
    });

    test('add purchase order dialog labels keep the English text (locale en)', async ({
        page,
    }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'en');
        }, LOCALE_KEY);

        await login(page);
        await openAddPurchaseOrderDialog(
            page,
            /^Add$/i,
            /Add New Purchase Order/i,
        );

        const dialog = page.getByRole('dialog', {
            name: /Add New Purchase Order/i,
        });
        await expect(dialog.getByText('PO Number', { exact: true })).toBeVisible();
        await expect(dialog.getByText('Supplier', { exact: true })).toBeVisible();
        await expect(dialog.getByText('Warehouse', { exact: true })).toBeVisible();
        await expect(
            dialog.getByText('Order Date', { exact: true }),
        ).toBeVisible();
        await expect(
            dialog.getByPlaceholder('Auto-generated if empty'),
        ).toBeVisible();
    });
});
