import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Period Closing E2E Tests', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
        await page.goto('/period-closings');
        await page.waitForResponse(
            (r) => r.url().includes('/api/period-closings') && r.status() < 400,
            { timeout: 15000 },
        );
    });

    test('can view Period Closings list', async ({ page }) => {
        await expect(page.locator('table')).toBeVisible();
        await expect(page.locator('tbody tr').first()).toBeVisible();
    });

    test('can search Period Closings', async ({ page }) => {
        const searchInput = page.getByPlaceholder(/Search/i);
        await expect(searchInput).toBeVisible();
        await searchInput.fill('2026');
        await searchInput.press('Enter');
        await page.waitForResponse(
            (r) => r.url().includes('/api/period-closings') && r.status() < 400,
            { timeout: 15000 },
        );
    });

    test('can open actions menu for Period Closing', async ({ page }) => {
        const row = page.locator('tbody tr').first();
        await expect(row).toBeVisible();
        await row.getByRole('button').last().click();

        const viewItem = page.getByRole('menuitem', { name: /View/i });
        await expect(viewItem).toBeVisible({ timeout: 5000 });
    });

    test('can export Period Closings', async ({ page }) => {
        const downloadPromise = page.waitForEvent('download');
        await page.getByRole('button', { name: /export/i }).click();
        const download = await downloadPromise;
        expect(download.suggestedFilename()).toBeTruthy();
    });

    test('Period Closings datatable has correct checkbox behavior', async ({ page }) => {
        const headerCheckboxes = page.locator('thead [data-testid="select-all"]');
        await expect(headerCheckboxes).toHaveCount(1);
        await expect(headerCheckboxes.first()).toBeVisible();

        const row = page.locator('tbody tr').first();
        await expect(row).toBeVisible();
        const bodyCheckbox = row.locator('[data-testid="select-row"]').first();
        await expect(bodyCheckbox).toBeVisible();
    });

    test('can sort Period Closings by all columns', async ({ page }) => {
        test.setTimeout(120000);
        const sortableColumns = [
            'Fiscal Year',
            'Period Month',
            'Period Year',
            'Closing Type',
            'Status',
            'Net Income',
            'Closed At',
        ];

        for (const column of sortableColumns) {
            const sortButton = page.locator('thead').getByRole('button', { name: column, exact: true });
            await expect(sortButton).toBeVisible();
            await sortButton.click();
            await page.waitForTimeout(500);
            await sortButton.click();
            await page.waitForTimeout(500);
        }
    });

    test('can filter Period Closings', async ({ page }) => {
        const filterButton = page.getByRole('button', { name: /filter/i });
        await filterButton.click();
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();
    });
});
