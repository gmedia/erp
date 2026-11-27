import { expect, test } from '@playwright/test';
import { login } from '../helpers';
import { VEHICLE_LOAN_PICKUP_PATH } from './helpers';

test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => localStorage.setItem('app_locale', 'id'));
});

test('pickup page shows Peminjaman Kendaraan as parent breadcrumb in id locale', async ({
    page,
}) => {
    await login(page);
    await page.goto(VEHICLE_LOAN_PICKUP_PATH);

    const breadcrumb = page.locator('nav[data-slot="breadcrumb"]');
    await expect(breadcrumb).toContainText('Peminjaman Kendaraan');
    await expect(breadcrumb).toContainText('Pengambilan');
});

test('sidebar highlights the vehicle loan module on pickup page', async ({
    page,
}) => {
    await login(page);
    await page.goto(VEHICLE_LOAN_PICKUP_PATH);

    const active = page.locator(
        '[data-slot="sidebar-content"] [data-active="true"]',
    );
    await expect(active).toHaveCount(1);
    await expect(active).toContainText('Permintaan');
});

test('pickup page in id locale does not show English approval wording', async ({
    page,
}) => {
    await login(page);
    await page.goto(VEHICLE_LOAN_PICKUP_PATH);

    await expect(page.locator('main')).not.toContainText('My Approvals');
});
