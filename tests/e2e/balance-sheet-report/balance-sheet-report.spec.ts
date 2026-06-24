import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Balance Sheet Report', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('can view balance sheet report', async ({ page }) => {
        test.setTimeout(60000);

        await page.goto('/reports/balance-sheet');

        await page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/balance-sheet') &&
                !r.url().includes('/export') &&
                r.status() < 400,
            { timeout: 30000 },
        );

        await expect(
            page.getByRole('heading', { name: 'Balance Sheet' }),
        ).toBeVisible();

        await expect(
            page.getByText('Assets', { exact: false }).first(),
        ).toBeVisible();
        await expect(
            page.getByText('Liabilities', { exact: false }).first(),
        ).toBeVisible();
        await expect(
            page.getByText('Equity', { exact: false }).first(),
        ).toBeVisible();
    });

    test('can change fiscal year selector', async ({ page }) => {
        test.setTimeout(60000);

        await page.goto('/reports/balance-sheet');

        await page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/balance-sheet') &&
                !r.url().includes('/export') &&
                r.status() < 400,
            { timeout: 30000 },
        );

        const fiscalYearSelector = page.getByRole('combobox').nth(0);
        await expect(fiscalYearSelector).toBeVisible();
        await fiscalYearSelector.click();

        const options = page.locator('[role="option"]:visible');
        await expect(options.first()).toBeVisible({ timeout: 5000 });
        const count = await options.count();
        // Pick last option to ensure it differs from current selection
        await options.nth(count - 1).click({ force: true });

        await page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/balance-sheet') &&
                !r.url().includes('/export') &&
                r.request().method() === 'GET' &&
                r.status() < 400,
            { timeout: 15000 },
        );
    });

    test('can change comparison year selector', async ({ page }) => {
        test.setTimeout(60000);

        await page.goto('/reports/balance-sheet');

        await page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/balance-sheet') &&
                !r.url().includes('/export') &&
                r.status() < 400,
            { timeout: 30000 },
        );

        const comparisonSelector = page.getByRole('combobox').nth(1);
        await expect(comparisonSelector).toBeVisible();
        await comparisonSelector.click();

        const options = page.locator('[role="option"]:visible');
        await expect(options.first()).toBeVisible({ timeout: 5000 });
        const count = await options.count();
        // Pick last option (a fiscal year, not "None") to trigger comparison refetch
        await options.nth(count - 1).click({ force: true });

        await page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/balance-sheet') &&
                !r.url().includes('/export') &&
                r.request().method() === 'GET' &&
                r.status() < 400,
            { timeout: 15000 },
        );
    });

    test('can change branch selector', async ({ page }) => {
        test.setTimeout(60000);

        await page.goto('/reports/balance-sheet');

        await page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/balance-sheet') &&
                !r.url().includes('/export') &&
                r.status() < 400,
            { timeout: 30000 },
        );

        const branchSelector = page.getByRole('combobox').nth(2);
        await expect(branchSelector).toBeVisible();
        await branchSelector.click();

        await page.waitForResponse(
            (r) => r.url().includes('/api/branches') && r.status() < 400,
            { timeout: 10000 },
        );

        const options = page.locator('ul.p-1 li button:visible');
        await expect(options.first()).toBeVisible({ timeout: 5000 });
        await options.first().click({ force: true });

        await page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/balance-sheet') &&
                !r.url().includes('/export') &&
                r.request().method() === 'GET' &&
                r.status() < 400,
            { timeout: 15000 },
        );
    });

    test('can export balance sheet report', async ({ page }) => {
        test.setTimeout(60000);

        await page.goto('/reports/balance-sheet');

        await page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/balance-sheet') &&
                !r.url().includes('/export') &&
                r.status() < 400,
            { timeout: 30000 },
        );

        const [response] = await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/api/reports/balance-sheet/export') &&
                    r.status() < 400,
                { timeout: 30000 },
            ),
            page.getByRole('button', { name: /^Export$/i }).click(),
        ]);

        const body = await response.json();
        expect(body).toHaveProperty('url');
        expect(body).toHaveProperty('filename');
        expect(body.filename).toMatch(/^balance_sheet_report_.*\.xlsx$/);
    });
});
