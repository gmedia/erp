import { test, expect } from '@playwright/test';
import { expectResponseOk, login } from '../helpers';

test.describe('Stock Monitor Dashboard', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('can view stock monitor summary and filter data', async ({ page }) => {
        const initialResponsePromise = page.waitForResponse(
            (r) =>
                r.url().includes('/stock-monitor') &&
                r.request().headers()['accept']?.includes('application/json'),
        );
        await page.goto('/stock-monitor');
        await page.waitForURL('**/stock-monitor', { timeout: 15000 });
        await expectResponseOk(await initialResponsePromise);

        await expect(page.getByText('Total SKU-Warehouse')).toBeVisible();
        await expect(page.locator('table')).toBeVisible();
        await expect(page.locator('tbody tr').first()).toBeVisible();

        const exportResponsePromise = page.waitForResponse(
            (r) => r.url().includes('/api/stock-monitor/export'),
        );
        await page.getByRole('button', { name: /export/i }).click();
        await expectResponseOk(await exportResponsePromise);

        const searchInput = page.getByRole('textbox').first();
        await searchInput.fill('P-');
        const searchResponsePromise = page.waitForResponse(
            (r) =>
                r.url().includes('/stock-monitor') &&
                r.request().headers()['accept']?.includes('application/json'),
        );
        await searchInput.press('Enter');
        await expectResponseOk(await searchResponsePromise);

        await expect(page.locator('tbody tr')).toHaveCount(1, {
            timeout: 15000,
        });

        await page.getByRole('button', { name: /filters/i }).click();
        await page.getByRole('dialog').getByPlaceholder('Example: 10').fill('1000');
        const filterResponsePromise = page.waitForResponse(
            (r) =>
                r.url().includes('/stock-monitor') &&
                r.request().headers()['accept']?.includes('application/json'),
        );
        await page.getByRole('button', { name: 'Apply Filters' }).click();
        await expectResponseOk(await filterResponsePromise);

        await expect(page.getByText('Low Stock Items')).toBeVisible();

    });

    test('menampilkan kartu nilai stok dalam perjalanan dari ringkasan', async ({ page }) => {
        const responsePromise = page.waitForResponse(
            (r) =>
                r.url().includes('/api/stock-monitor') &&
                !r.url().includes('export') &&
                r.request().headers()['accept']?.includes('application/json'),
        );
        await page.goto('/stock-monitor');
        const response = await responsePromise;
        await expectResponseOk(response);

        const body = await response.json();
        expect(body.summary.in_transit).toEqual(
            expect.objectContaining({
                total_items: expect.any(Number),
                total_quantity: expect.any(String),
                total_value: expect.any(String),
            }),
        );

        await expect(page.getByText('In-Transit Stock Value')).toBeVisible();
    });
});
