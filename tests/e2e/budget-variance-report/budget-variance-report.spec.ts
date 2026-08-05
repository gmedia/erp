import { expect, test } from '@playwright/test';
import { login } from '../helpers';
import {
    applyStatusFilter,
    ensureBudgetExists,
    openBudgetVarianceReport,
    waitForBudgetVarianceReportResponse,
} from './helpers';

test.describe('Budget Variance Report', () => {
    test.setTimeout(120000);

    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
        await ensureBudgetExists(page);
    });

    test('can navigate to report page and see filters', async ({ page }) => {
        await page.goto('/reports/budget-variance');
        await page.waitForURL('**/reports/budget-variance', { timeout: 15000 });

        const filterButton = page.getByRole('button', { name: /filters/i });
        await expect(filterButton).toBeVisible({ timeout: 15000 });
        await filterButton.click();

        const filtersDialog = page.getByRole('dialog');
        await expect(filtersDialog).toBeVisible();

        await expect(
            filtersDialog
                .locator('button')
                .filter({ hasText: /Select budget|Budget/i })
                .first(),
        ).toBeVisible();

        await expect(
            filtersDialog.getByRole('button', { name: /Apply Filters/i }),
        ).toBeVisible();
    });

    test('can view report after selecting budget', async ({ page }) => {
        await openBudgetVarianceReport(page);

        await expect(page.locator('table').first()).toBeVisible();
        const rows = page.locator('table tbody tr');
        const rowCount = await rows.count();
        if (rowCount > 0) {
            await expect(rows.first()).toBeVisible();
        }
    });

    test('can filter by status', async ({ page }) => {
        await openBudgetVarianceReport(page);
        await applyStatusFilter(page, /Over Budget/i);
        await expect(page.locator('table').first()).toBeVisible();
    });

    test('can filter by account type', async ({ page }) => {
        await openBudgetVarianceReport(page);

        await page.getByRole('button', { name: /filters/i }).click();
        const filtersDialog = page.getByRole('dialog');
        await expect(filtersDialog).toBeVisible();

        const typeTrigger = filtersDialog
            .locator('button')
            .filter({ hasText: /All types|Asset|Liability|Equity|Revenue|Expense/i })
            .first();
        await typeTrigger.click({ force: true });

        const option = page
            .locator('[role="option"]:visible')
            .filter({ hasText: /Expense/i })
            .first();
        await expect(option).toBeVisible({ timeout: 10000 });
        await option.click({ force: true });

        await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/api/reports/budget-variance') &&
                    !r.url().includes('/export') &&
                    r.url().includes('account_type=') &&
                    r.status() < 400,
                { timeout: 30000 },
            ),
            filtersDialog.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);

        await expect(page.locator('table').first()).toBeVisible();
    });

    test('can export budget variance report', async ({ page }) => {
        await openBudgetVarianceReport(page);

        const exportButton = page.getByRole('button', { name: /^Export$/i });
        await expect(exportButton).toBeEnabled({ timeout: 10000 });

        const [response] = await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/api/reports/budget-variance/export') &&
                    r.status() < 400,
                { timeout: 30000 },
            ),
            exportButton.click(),
        ]);

        const body = await response.json();
        expect(body).toHaveProperty('url');
        expect(body).toHaveProperty('filename');
        expect(body.filename).toMatch(/^budget_variance_.*\.xlsx$/);
    });

    test('can sort by Account Code column', async ({ page }) => {
        await openBudgetVarianceReport(page);

        const sortButton = page.getByRole('button', {
            name: 'Account Code',
            exact: true,
        });
        if (await sortButton.isVisible().catch(() => false)) {
            await sortButton.click({ force: true });
            await waitForBudgetVarianceReportResponse(page).catch(() => null);
            await expect(page.locator('table').first()).toBeVisible();
        }
    });
});
