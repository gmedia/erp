import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Stock Movements (Kartu Stok)', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
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
        await searchInput.fill(refNumber);
        await searchInput.press('Enter');

        await page
            .waitForResponse(
                (r) =>
                    r.url().includes('/stock-movements') &&
                    r.request().headers()['accept']?.includes('application/json') &&
                    r.status() < 400,
            )
            .catch(() => null);

        await expect(page.getByText(refNumber).first()).toBeVisible();

        await refLink.click();

        if (refNumber.toUpperCase().startsWith('ST-')) {
            await expect(page).toHaveURL(/\/stock-transfers\?search=/);
            await page
                .waitForResponse(
                    (r) =>
                        r.url().includes('/api/stock-transfers') &&
                        r.status() < 400,
                )
                .catch(() => null);
        } else if (refNumber.toUpperCase().startsWith('SA-')) {
            await expect(page).toHaveURL(/\/stock-adjustments\?search=/);
            await page
                .waitForResponse(
                    (r) =>
                        r.url().includes('/api/stock-adjustments') &&
                        r.status() < 400,
                )
                .catch(() => null);
        } else {
            await expect(page).toHaveURL(/\/inventory-stocktakes\?search=/);
            await page
                .waitForResponse(
                    (r) =>
                        r.url().includes('/api/inventory-stocktakes') &&
                        r.status() < 400,
                )
                .catch(() => null);
        }

        await expect(page.getByRole('textbox').first()).toHaveValue(refNumber);
    });
});

