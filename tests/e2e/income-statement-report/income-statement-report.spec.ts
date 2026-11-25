import { test, expect } from '@playwright/test';
import { login, expectResponseOk } from '../helpers';

test.describe('Income Statement Report', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('can view income statement report', async ({ page }) => {
        test.setTimeout(60000);

        const initialResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/income-statement') &&
                !r.url().includes('/export') &&
                r.request().method() === 'GET',
            { timeout: 30000 },
        );
        await page.goto('/reports/income-statement');
        await expectResponseOk(await initialResponse);

        await expect(
            page.getByRole('heading', { name: 'Income Statement' }),
        ).toBeVisible();

        await expect(page.getByText('Revenue').first()).toBeVisible();
    });

    test('can change fiscal year selector', async ({ page }) => {
        test.setTimeout(60000);

        const initialResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/income-statement') &&
                !r.url().includes('/export') &&
                r.request().method() === 'GET',
            { timeout: 30000 },
        );
        await page.goto('/reports/income-statement');
        await expectResponseOk(await initialResponse);

        const fiscalYearSelector = page.getByRole('combobox').nth(0);
        await expect(fiscalYearSelector).toBeVisible();
        await fiscalYearSelector.click();

        const options = page.locator('[role="option"]:visible');
        await expect(options.first()).toBeVisible({ timeout: 5000 });
        const count = await options.count();
        const fiscalYearChange = page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/income-statement') &&
                !r.url().includes('/export') &&
                r.request().method() === 'GET',
            { timeout: 15000 },
        );
        await options.nth(count - 1).click({ force: true });
        await expectResponseOk(await fiscalYearChange);
    });

    test('can change comparison year selector', async ({ page }) => {
        test.setTimeout(60000);

        const initialResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/income-statement') &&
                !r.url().includes('/export') &&
                r.request().method() === 'GET',
            { timeout: 30000 },
        );
        await page.goto('/reports/income-statement');
        await expectResponseOk(await initialResponse);

        const comparisonSelector = page.getByRole('combobox').nth(1);
        await expect(comparisonSelector).toBeVisible();
        await comparisonSelector.click();

        const options = page.locator('[role="option"]:visible');
        await expect(options.first()).toBeVisible({ timeout: 5000 });
        const count = await options.count();
        const comparisonChange = page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/income-statement') &&
                !r.url().includes('/export') &&
                r.request().method() === 'GET',
            { timeout: 15000 },
        );
        await options.nth(count - 1).click({ force: true });
        await expectResponseOk(await comparisonChange);
    });

    test('can change branch selector', async ({ page }) => {
        test.setTimeout(60000);

        const initialResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/income-statement') &&
                !r.url().includes('/export') &&
                r.request().method() === 'GET',
            { timeout: 30000 },
        );
        await page.goto('/reports/income-statement');
        await expectResponseOk(await initialResponse);

        const branchSelector = page.getByRole('combobox').nth(2);
        await expect(branchSelector).toBeVisible();
        const branchesResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/branches') &&
                r.request().method() === 'GET',
            { timeout: 10000 },
        );
        await branchSelector.click();
        await expectResponseOk(await branchesResponse);

        const options = page.locator('ul.p-1 li button:visible');
        await expect(options.first()).toBeVisible({ timeout: 5000 });

        const branchChange = page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/income-statement') &&
                !r.url().includes('/export') &&
                r.request().method() === 'GET',
            { timeout: 15000 },
        );
        await options.first().click({ force: true });
        await expectResponseOk(await branchChange);
    });

    test('can export income statement report', async ({ page }) => {
        test.setTimeout(60000);

        const initialResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/income-statement') &&
                !r.url().includes('/export') &&
                r.request().method() === 'GET',
            { timeout: 30000 },
        );
        await page.goto('/reports/income-statement');
        await expectResponseOk(await initialResponse);

        const exportResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/income-statement/export') &&
                r.request().method() === 'POST',
            { timeout: 30000 },
        );
        await page.getByRole('button', { name: /^Export$/i }).click();
        const response = await exportResponse;
        await expectResponseOk(response);

        const body = await response.json();
        expect(body).toHaveProperty('url');
        expect(body).toHaveProperty('filename');
        expect(body.filename).toMatch(/^income_statement_report_.*\.xlsx$/);
    });
});
