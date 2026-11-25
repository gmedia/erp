import { test, expect } from '@playwright/test';
import { login, expectResponseOk } from '../helpers';

test.describe('Trial Balance Detailed Report', () => {
    test.setTimeout(90000);

    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('shows instructive prompt before fiscal year is selected', async ({
        page,
    }) => {
        await page.goto('/reports/trial-balance-detailed');

        await expect(
            page.getByTestId('report-filter-prompt'),
        ).toBeVisible({ timeout: 15000 });
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
        const listResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/trial-balance-detailed') &&
                !r.url().includes('/export') &&
                r.request().method() === 'GET',
            { timeout: 30000 },
        );
        await option.click({ force: true });
        await filtersDialog.getByRole('button', { name: 'Apply Filters' }).click();
        await expectResponseOk(await listResponse);
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

        const listResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/trial-balance-detailed') &&
                !r.url().includes('/export') &&
                r.request().method() === 'GET',
            { timeout: 30000 },
        );
        await filtersDialog.getByRole('button', { name: 'Apply Filters' }).click();
        await expectResponseOk(await listResponse);

        const exportButton = page.getByRole('button', { name: /^Export$/i });
        await expect(exportButton).toBeEnabled({ timeout: 10000 });

        const exportResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/trial-balance-detailed/export') &&
                r.request().method() === 'POST',
            { timeout: 30000 },
        );
        await exportButton.click();
        const response = await exportResponse;
        await expectResponseOk(response);

        const body = await response.json();
        expect(body).toHaveProperty('url');
        expect(body).toHaveProperty('filename');
        expect(body.filename).toMatch(/^trial_balance_report_.*\.xlsx$/);
    });
});
