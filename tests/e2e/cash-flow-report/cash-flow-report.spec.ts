import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Cash Flow Report', () => {
    test.setTimeout(90000);

    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('can view cash flow report', async ({ page }) => {
        const responsePromise = page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/cash-flow') &&
                !r.url().includes('/export') &&
                r.status() < 400,
            { timeout: 30000 },
        );

        await page.goto('/reports/cash-flow');
        await responsePromise;

        await expect(
            page.getByRole('heading', { name: 'Cash Flow' }),
        ).toBeVisible();
        await expect(page.locator('table').first()).toBeVisible();
    });

    test('can change fiscal year selector', async ({ page }) => {
        const initialResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/cash-flow') &&
                !r.url().includes('/export') &&
                r.status() < 400,
            { timeout: 30000 },
        );

        await page.goto('/reports/cash-flow');
        await initialResponse;

        const fiscalYearSelector = page.getByRole('combobox').first();
        await expect(fiscalYearSelector).toBeVisible();
        await fiscalYearSelector.click();

        const options = page.locator('[role="option"]:visible');
        await expect(options.first()).toBeVisible({ timeout: 5000 });

        // Pick the last option to avoid selecting the already-active year
        const optionCount = await options.count();
        const targetOption = options.nth(optionCount - 1);

        const refetchPromise = page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/cash-flow') &&
                !r.url().includes('/export') &&
                r.status() < 400,
            { timeout: 15000 },
        );

        await targetOption.click({ force: true });
        await refetchPromise;
    });

    test('can export cash flow report', async ({ page }) => {
        const initialResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/cash-flow') &&
                !r.url().includes('/export') &&
                r.status() < 400,
            { timeout: 30000 },
        );

        await page.goto('/reports/cash-flow');
        await initialResponse;

        const downloadPromise = page.waitForEvent('download', {
            timeout: 30000,
        });

        await page.getByRole('button', { name: 'Export' }).click();

        const download = await downloadPromise;
        expect(download.suggestedFilename()).toMatch(
            /^cash_flow_report_.*\.xlsx$/,
        );
    });
});
