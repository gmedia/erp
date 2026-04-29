import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Stock Movements (Kartu Stok)', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('can view stock movements and drill down by reference', async ({
        page,
    }) => {
        await page.goto('/stock-movements');
        await page.waitForURL('**/stock-movements', { timeout: 15000 });

        // Wait for the table to be visible
        await expect(page.locator('table')).toBeVisible({ timeout: 15000 });

        // Wait for actual data rows to appear (not the "No results." row)
        const refLink = page
            .locator('tbody a')
            .filter({ hasText: /^(ST|SA|SO)-/ })
            .first();

        await expect(refLink).toBeVisible({ timeout: 30000 });
        const refNumber = await refLink.innerText();

        const searchInput = page.getByRole('textbox').first();
        const searchResponsePromise = page.waitForResponse(
            (r) =>
                r.url().includes('/stock-movements') &&
                r.request().headers()['accept']?.includes('application/json') &&
                r.status() < 400,
        );
        await searchInput.fill(refNumber);
        await searchInput.press('Enter');
        await searchResponsePromise;

        await expect(page.getByText(refNumber).first()).toBeVisible();

        if (refNumber.toUpperCase().startsWith('ST-')) {
            await Promise.all([
                page.waitForURL(/\/stock-transfers\?search=/),
                page.waitForResponse(
                    (r) =>
                        r.url().includes('/api/stock-transfers') &&
                        r.status() < 400,
                ),
                refLink.click(),
            ]);
        } else if (refNumber.toUpperCase().startsWith('SA-')) {
            await Promise.all([
                page.waitForURL(/\/stock-adjustments\?search=/),
                page.waitForResponse(
                    (r) =>
                        r.url().includes('/api/stock-adjustments') &&
                        r.status() < 400,
                ),
                refLink.click(),
            ]);
        } else {
            await Promise.all([
                page.waitForURL(/\/inventory-stocktakes\?search=/),
                page.waitForResponse(
                    (r) =>
                        r.url().includes('/api/inventory-stocktakes') &&
                        r.status() < 400,
                ),
                refLink.click(),
            ]);
        }

        await expect(page.getByRole('textbox').first()).toHaveValue(refNumber);
    });
});

