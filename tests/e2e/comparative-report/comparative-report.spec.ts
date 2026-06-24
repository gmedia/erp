import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Comparative Report', () => {
    test.setTimeout(90000);

    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('can view comparative report', async ({ page }) => {
        await page.goto('/reports/comparative');

        await expect(
            page.getByRole('heading', { name: 'Comparative Report' }),
        ).toBeVisible({ timeout: 30000 });

        await expect(page.getByText('Assets').first()).toBeVisible();
        await expect(page.getByText('Revenue').first()).toBeVisible();
    });

    test('can change fiscal year selector', async ({ page }) => {
        await page.goto('/reports/comparative');

        await expect(
            page.getByRole('heading', { name: 'Comparative Report' }),
        ).toBeVisible({ timeout: 30000 });

        const fiscalYearSelector = page.getByRole('combobox').nth(0);
        await expect(fiscalYearSelector).toBeVisible();
        await fiscalYearSelector.click();

        const options = page.locator('[role="option"]:visible');
        await expect(options.first()).toBeVisible({ timeout: 5000 });
        const optionCount = await options.count();
        const yearOption = options.nth(optionCount - 1);

        const refreshPromise = page.waitForResponse(
            (r) =>
                (r.url().includes('/api/reports/comparative') &&
                    !r.url().includes('/export') &&
                    r.request().method() === 'GET' &&
                    r.status() < 400,
            { timeout: 30000 },
        );

        await yearOption.click({ force: true });
        await refreshPromise;

        await expect(
            page.getByRole('heading', { name: 'Comparative Report' }),
        ).toBeVisible();
    });

    test('can change comparison year selector', async ({ page }) => {
        await page.goto('/reports/comparative');

        await expect(
            page.getByRole('heading', { name: 'Comparative Report' }),
        ).toBeVisible({ timeout: 30000 });

        const comparisonSelector = page.getByRole('combobox').nth(1);
        await expect(comparisonSelector).toBeVisible();
        await comparisonSelector.click();

        const options = page.locator('[role="option"]:visible');
        await expect(options.first()).toBeVisible({ timeout: 5000 });

        await Promise.all([
            page.waitForResponse(
                (r) =>
                    (r.url().includes('/api/reports/comparative') &&
                    !r.url().includes('/export') &&
                    r.request().method() === 'GET' &&
                    r.status() < 400,
                { timeout: 30000 },
            ),
            options.first().click({ force: true }),
        ]);

        await expect(
            page.getByRole('heading', { name: 'Comparative Report' }),
        ).toBeVisible();
    });

    test('can change branch selector', async ({ page }) => {
        await page.goto('/reports/comparative');

        await expect(
            page.getByRole('heading', { name: 'Comparative Report' }),
        ).toBeVisible({ timeout: 30000 });

        const branchSelector = page.getByRole('combobox').nth(2);
        await expect(branchSelector).toBeVisible();
        await branchSelector.click();

        await page.waitForResponse(
            (r) =>
                r.url().includes('/api/branches') &&
                r.status() < 400,
            { timeout: 30000 },
        );

        const options = page.locator('ul.p-1 li button:visible');
        await expect(options.first()).toBeVisible({ timeout: 5000 });

        await Promise.all([
            page.waitForResponse(
                (r) =>
                    (r.url().includes('/api/reports/comparative') &&
                    !r.url().includes('/export') &&
                    r.request().method() === 'GET' &&
                    r.status() < 400,
                { timeout: 30000 },
            ),
            options.first().click({ force: true }),
        ]);

        await expect(
            page.getByRole('heading', { name: 'Comparative Report' }),
        ).toBeVisible();
    });

    test('can export comparative report', async ({ page }) => {
        await page.goto('/reports/comparative');

        await expect(
            page.getByRole('heading', { name: 'Comparative Report' }),
        ).toBeVisible({ timeout: 30000 });

        const [download] = await Promise.all([
            page.waitForEvent('download', { timeout: 30000 }),
            page.getByRole('button', { name: 'Export', exact: true }).click(),
        ]);

        const filename = download.suggestedFilename();
        expect(filename).toMatch(/^comparative_report_.*\.xlsx$/);
    });
});
