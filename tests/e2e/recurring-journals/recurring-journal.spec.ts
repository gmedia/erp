import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Recurring Journal E2E Tests', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
        await page.goto('/recurring-journals');
        await page.waitForResponse(
            (r) => r.url().includes('/api/recurring-journals') && r.status() < 400,
            { timeout: 15000 },
        );
    });

    test('can view Recurring Journals list', async ({ page }) => {
        await expect(page.locator('table')).toBeVisible();
        await expect(page.locator('tbody tr').first()).toBeVisible();
    });

    test('can search Recurring Journals', async ({ page }) => {
        const searchInput = page.getByPlaceholder(/Search name, description/i);
        await expect(searchInput).toBeVisible();
        await searchInput.fill('Sample');
        await searchInput.press('Enter');
        await page.waitForResponse(
            (r) => r.url().includes('/api/recurring-journals') && r.status() < 400,
            { timeout: 15000 },
        );
        await expect(page.locator('tbody tr').first()).toBeVisible();
    });

    test('can view Recurring Journal details', async ({ page }) => {
        const row = page.locator('tbody tr').first();
        await expect(row).toBeVisible();
        await row.getByRole('button').last().click();

        const viewItem = page.getByRole('menuitem', { name: /View/i });
        await expect(viewItem).toBeVisible({ timeout: 5000 });
        await viewItem.click();

        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible({ timeout: 10000 });
    });

    test('can export Recurring Journals', async ({ page }) => {
        const downloadPromise = page.waitForEvent('download');
        await page.getByRole('button', { name: /export/i }).click();
        const download = await downloadPromise;
        expect(download.suggestedFilename()).toBeTruthy();
    });

    test('Recurring Journals datatable has correct checkbox behavior', async ({ page }) => {
        const headerCheckboxes = page.locator('thead [data-testid="select-all"]');
        await expect(headerCheckboxes).toHaveCount(1);
        await expect(headerCheckboxes.first()).toBeVisible();

        const row = page.locator('tbody tr').first();
        await expect(row).toBeVisible();
        const bodyCheckbox = row.locator('[data-testid="select-row"]').first();
        await expect(bodyCheckbox).toBeVisible();
    });

    test('can sort Recurring Journals by all columns', async ({ page }) => {
        test.setTimeout(120000);
        const sortableColumns = [
            'Name',
            'Frequency',
            'Next Run Date',
            'Total Amount',
            'Auto Post',
            'Is Active',
            'Created At',
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

    test('can filter Recurring Journals', async ({ page }) => {
        const filterButton = page.getByRole('button', { name: /filter/i });
        await filterButton.click();
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();
    });
});
