import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Trial Balance Detailed Report', () => {
    test.setTimeout(90000);

    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('can view trial balance detailed report', async ({ page }) => {
        await page.goto('/reports/trial-balance-detailed');

        await expect(page.locator('table').first()).toBeVisible({ timeout: 30000 });
    });

    test('can change fiscal year filter', async ({ page }) => {
        await page.goto('/reports/trial-balance-detailed');
        await expect(page.locator('table').first()).toBeVisible({ timeout: 30000 });

        await page.getByRole('button', { name: /filters/i }).click();
        const filtersDialog = page.getByRole('dialog');
        await expect(filtersDialog).toBeVisible();

        const fiscalYearTrigger = filtersDialog
            .locator('button')
            .filter({ hasText: /Select fiscal year/i })
            .first();
        await fiscalYearTrigger.click({ force: true });

        const option = page
            .locator('[role="option"]:visible, ul[aria-busy]:visible button:visible')
            .first();
        await expect(option).toBeVisible({ timeout: 10000 });
        await option.click({ force: true });

        await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/api/reports/trial-balance-detailed') &&
                    !r.url().includes('/export') &&
                    r.status() < 400,
                { timeout: 30000 },
            ),
            filtersDialog.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);
    });

    test('can export trial balance detailed report', async ({ page }) => {
        await page.goto('/reports/trial-balance-detailed');
        await expect(page.locator('table').first()).toBeVisible({ timeout: 30000 });

        await page.getByRole('button', { name: /filters/i }).click();
        const filtersDialog = page.getByRole('dialog');
        await expect(filtersDialog).toBeVisible();

        await filtersDialog
            .locator('button')
            .filter({ hasText: /Select fiscal year/i })
            .first()
            .click({ force: true });

        const option = page
            .locator('[role="option"]:visible, ul[aria-busy]:visible button:visible')
            .first();
        await expect(option).toBeVisible({ timeout: 10000 });
        await option.click({ force: true });

        await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/api/reports/trial-balance-detailed') &&
                    !r.url().includes('/export') &&
                    r.status() < 400,
                { timeout: 30000 },
            ),
            filtersDialog.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);

        const exportButton = page.getByRole('button', { name: /^Export$/i });
        await expect(exportButton).toBeEnabled({ timeout: 10000 });

        const [response] = await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/api/reports/trial-balance-detailed/export') &&
                    r.status() < 400,
                { timeout: 30000 },
            ),
            exportButton.click(),
        ]);

        const body = await response.json();
        expect(body).toHaveProperty('url');
        expect(body).toHaveProperty('filename');
        expect(body.filename).toMatch(/^trial_balance_report_.*\.xlsx$/);
    });
});
