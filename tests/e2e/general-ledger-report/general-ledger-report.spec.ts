import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('General Ledger Report', () => {
    test.setTimeout(90000);

    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('can navigate to report page and see filters', async ({ page }) => {
        await page.goto('/reports/general-ledger');
        await page.waitForURL('**/reports/general-ledger', { timeout: 15000 });

        const filterButton = page.getByRole('button', { name: /filter/i });
        await expect(filterButton).toBeVisible({ timeout: 15000 });
        await filterButton.click();

        const filtersDialog = page.getByRole('dialog');
        await expect(filtersDialog).toBeVisible();

        const accountTrigger = filtersDialog.locator('button[role="combobox"]').first();
        await expect(accountTrigger).toBeVisible();

        const applyBtn = filtersDialog.getByRole('button', { name: /Apply Filters/i });
        await expect(applyBtn).toBeVisible();
    });

    test('can view general ledger report table', async ({ page }) => {
        await page.goto('/reports/general-ledger');

        await expect(page.locator('table').first()).toBeVisible({ timeout: 30000 });

        const rows = page.locator('table tbody tr');
        await expect(rows.first()).toBeVisible({ timeout: 15000 });
    });

    test('can filter by account', async ({ page }) => {
        await page.goto('/reports/general-ledger');
        await expect(page.locator('table').first()).toBeVisible({ timeout: 30000 });

        await page.getByRole('button', { name: /filters/i }).click();
        const filtersDialog = page.getByRole('dialog');
        await expect(filtersDialog).toBeVisible();

        const accountTrigger = filtersDialog.locator('button[role="combobox"]').first();
        await accountTrigger.click({ force: true });

        const option = page
            .locator('ul.p-1 li button:visible')
            .first();
        await expect(option).toBeVisible({ timeout: 10000 });
        await option.click({ force: true });

        await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/api/reports/general-ledger') &&
                    !r.url().includes('/export') &&
                    r.status() < 400,
                { timeout: 30000 },
            ),
            filtersDialog.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);
    });

    test('can filter by date range', async ({ page }) => {
        await page.goto('/reports/general-ledger');
        await expect(page.locator('table').first()).toBeVisible({ timeout: 30000 });

        await page.getByRole('button', { name: /filters/i }).click();
        const filtersDialog = page.getByRole('dialog');
        await expect(filtersDialog).toBeVisible();

        const dateInputs = filtersDialog.locator('input[type="text"]');
        const count = await dateInputs.count();

        if (count >= 2) {
            await dateInputs.nth(0).fill('2024-01-01');
            await dateInputs.nth(1).fill('2024-12-31');
        }

        await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/api/reports/general-ledger') &&
                    !r.url().includes('/export') &&
                    r.status() < 400,
                { timeout: 30000 },
            ),
            filtersDialog.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);
    });

    test('can export general ledger report', async ({ page }) => {
        await page.goto('/reports/general-ledger');
        await expect(page.locator('table').first()).toBeVisible({ timeout: 30000 });

        await page.getByRole('button', { name: /filters/i }).click();
        const filtersDialog = page.getByRole('dialog');
        await expect(filtersDialog).toBeVisible();

        const accountTrigger = filtersDialog.locator('button[role="combobox"]').first();
        await accountTrigger.click({ force: true });

        const option = page
            .locator('ul.p-1 li button:visible')
            .first();
        await expect(option).toBeVisible({ timeout: 10000 });
        await option.click({ force: true });

        await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/api/reports/general-ledger') &&
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
                    r.url().includes('/api/reports/general-ledger/export') &&
                    r.status() < 400,
                { timeout: 30000 },
            ),
            exportButton.click(),
        ]);

        const body = await response.json();
        expect(body).toHaveProperty('url');
        expect(body).toHaveProperty('filename');
        expect(body.filename).toMatch(/^general_ledger.*\.xlsx$/);
    });
});
