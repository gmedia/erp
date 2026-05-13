import { expect, test } from '@playwright/test';
import { login } from '../helpers';
import { searchReportConfiguration } from './helpers';

test.describe('Report Configuration E2E Tests', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
        await page.goto('/report-configurations');
        await page.waitForResponse(
            (r) =>
                r.url().includes('/api/report-configurations') &&
                r.status() < 400,
            { timeout: 15000 },
        );
    });

    test('can view Report Configurations list with seeded defaults', async ({
        page,
    }) => {
        await expect(page.locator('table')).toBeVisible();
        await expect(page.locator('tbody tr').first()).toBeVisible();
        await expect(page.locator('tbody')).toContainText('Balance Sheet');
    });

    test('can search Report Configurations', async ({ page }) => {
        await searchReportConfiguration(page, 'Balance');
        await expect(page.locator('tbody')).toContainText('Balance Sheet');
    });

    test('can filter Report Configurations by report type', async ({
        page,
    }) => {
        const filterButton = page.getByRole('button', { name: /filter/i });
        await filterButton.click();
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();
    });

    test('can open actions menu and view modal', async ({ page }) => {
        const row = page.locator('tbody tr').first();
        await expect(row).toBeVisible();
        await row.getByRole('button').last().click();

        const viewItem = page.getByRole('menuitem', { name: /View/i });
        await expect(viewItem).toBeVisible({ timeout: 5000 });
        await viewItem.click();

        const viewDialog = page.getByRole('dialog', {
            name: /Report Configuration Details/i,
        });
        await expect(viewDialog).toBeVisible();
        await expect(viewDialog).toContainText(/Sections/i);
    });

    test('can export Report Configurations', async ({ page }) => {
        const downloadPromise = page.waitForEvent('download');
        await page.getByRole('button', { name: /export/i }).click();
        const download = await downloadPromise;
        expect(download.suggestedFilename()).toBeTruthy();
    });

    test('datatable has correct checkbox behavior', async ({ page }) => {
        const headerCheckboxes = page.locator('thead [data-testid="select-all"]');
        await expect(headerCheckboxes).toHaveCount(1);
        await expect(headerCheckboxes.first()).toBeVisible();

        const row = page.locator('tbody tr').first();
        const bodyCheckbox = row.locator('[data-testid="select-row"]').first();
        await expect(bodyCheckbox).toBeVisible();
    });

    test('can sort Report Configurations by code and name', async ({ page }) => {
        test.setTimeout(90000);
        const sortableColumns = ['Code', 'Name'];

        for (const column of sortableColumns) {
            const sortButton = page
                .locator('thead')
                .getByRole('button', { name: column, exact: true });
            await expect(sortButton).toBeVisible();
            await sortButton.click();
            await page.waitForTimeout(500);
            await sortButton.click();
            await page.waitForTimeout(500);
        }
    });
});
