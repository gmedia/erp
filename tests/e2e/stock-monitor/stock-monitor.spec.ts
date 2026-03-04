import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Stock Monitor Dashboard', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('can view stock monitor summary and filter data', async ({ page }) => {
        await page.goto('/stock-monitor');
        await page.waitForURL('**/stock-monitor', { timeout: 15000 });

        await page
            .waitForResponse(
                (r) =>
                    r.url().includes('/stock-monitor') &&
                    r.request().headers()['accept']?.includes(
                        'application/json',
                    ) &&
                    r.status() < 400,
            )
            .catch(() => null);

        await expect(page.getByText('Total SKU-Warehouse')).toBeVisible();
        await expect(page.locator('table')).toBeVisible();
        await expect(page.locator('tbody tr').first()).toBeVisible();

        const exportResponsePromise = page.waitForResponse(
            (r) =>
                r.url().includes('/api/stock-monitor/export') &&
                r.status() < 400,
        );
        await page.getByRole('button', { name: /export/i }).click();
        await exportResponsePromise;

        const searchInput = page.getByRole('textbox').first();
        await searchInput.fill('P-');
        await searchInput.press('Enter');

        await page
            .waitForResponse(
                (r) =>
                    r.url().includes('/stock-monitor') &&
                    r.request().headers()['accept']?.includes(
                        'application/json',
                    ) &&
                    r.status() < 400,
            )
            .catch(() => null);

        await expect(page.locator('tbody tr')).toHaveCount(1, {
            timeout: 15000,
        });

        await page.getByRole('button', { name: /filters/i }).click();
        await page.getByRole('dialog').getByPlaceholder('Example: 10').fill('1000');
        await page.getByRole('button', { name: 'Apply Filters' }).click();

        await page
            .waitForResponse(
                (r) =>
                    r.url().includes('/stock-monitor') &&
                    r.request().headers()['accept']?.includes(
                        'application/json',
                    ) &&
                    r.status() < 400,
            )
            .catch(() => null);

        await expect(page.getByText('Low Stock Items')).toBeVisible();

    });
});
