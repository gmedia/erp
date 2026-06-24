import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Income Statement Report', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('can view income statement report', async ({ page }) => {
        test.setTimeout(60000);

        await page.goto('/reports/income-statement');

        await page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/income-statement') &&
                !r.url().includes('/export') &&
                r.status() < 400,
            { timeout: 30000 },
        );

        await expect(
            page.getByRole('heading', { name: 'Income Statement' }),
        ).toBeVisible();

        await expect(page.getByText('Revenue').first()).toBeVisible();
    });

    test('can change fiscal year selector', async ({ page }) => {
        test.setTimeout(60000);

        await page.goto('/reports/income-statement');

        await page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/income-statement') &&
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
        await options.nth(count - 1).click({ force: true });

        await page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/income-statement') &&
                !r.url().includes('/export') &&
                r.request().method() === 'GET' &&
                r.status() < 400,
            { timeout: 15000 },
        );
    });

    test('can change comparison year selector', async ({ page }) => {
        test.setTimeout(60000);

        await page.goto('/reports/income-statement');

        await page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/income-statement') &&
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
        await options.nth(count - 1).click({ force: true });

        await page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/income-statement') &&
                !r.url().includes('/export') &&
                r.request().method() === 'GET' &&
                r.status() < 400,
            { timeout: 15000 },
        );
    });

    test('can change branch selector', async ({ page }) => {
        test.setTimeout(60000);

        await page.goto('/reports/income-statement');

        await page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/income-statement') &&
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
                r.url().includes('/api/reports/income-statement') &&
                !r.url().includes('/export') &&
                r.request().method() === 'GET' &&
                r.status() < 400,
            { timeout: 15000 },
        );
    });

    test('can export income statement report', async ({ page }) => {
        test.setTimeout(60000);

        await page.goto('/reports/income-statement');

        await page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/income-statement') &&
                !r.url().includes('/export') &&
                r.status() < 400,
            { timeout: 30000 },
        );

        const [response] = await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes(
                        '/api/reports/income-statement/export',
                    ) && r.status() < 400,
                { timeout: 30000 },
            ),
            page.getByRole('button', { name: /^Export$/i }).click(),
        ]);

        const body = await response.json();
        expect(body).toHaveProperty('url');
        expect(body).toHaveProperty('filename');
        expect(body.filename).toMatch(/^income_statement_report_.*\.xlsx$/);
    });
});
