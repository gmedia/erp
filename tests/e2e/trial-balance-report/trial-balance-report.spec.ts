import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Trial Balance Report', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('can view trial balance report with fiscal year selector', async ({ page }) => {
        await page.goto('/reports/trial-balance');
        await page.waitForURL('**/reports/trial-balance', { timeout: 15000 });

        await page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/trial-balance') &&
                r.status() < 400,
            { timeout: 30000 },
        );

        await expect(page.getByRole('heading', { name: 'Trial Balance' })).toBeVisible();

        const debitHeader = page.getByText('Debit', { exact: true }).first();
        const creditHeader = page.getByText('Credit', { exact: true }).first();
        await expect(debitHeader).toBeVisible();
        await expect(creditHeader).toBeVisible();

        const fiscalYearSelector = page.getByRole('combobox').first();
        if (await fiscalYearSelector.isVisible().catch(() => false)) {
            await fiscalYearSelector.click();
            const yearOption = page
                .locator('[role="option"]:visible')
                .first();
            if (await yearOption.isVisible({ timeout: 5000 }).catch(() => false)) {
                await yearOption.click({ force: true });
                await page.waitForTimeout(1000);
            }
        }
    });

    test('can export trial balance report', async ({ page }) => {
        test.setTimeout(60000);

        await page.goto('/reports/trial-balance');
        await page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/trial-balance') &&
                !r.url().includes('/export') &&
                r.status() < 400,
            { timeout: 30000 },
        );

        const [exportResponse] = await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/api/reports/trial-balance/export') &&
                    r.status() < 400,
                { timeout: 30000 },
            ),
            page.getByRole('button', { name: /export/i }).click(),
        ]);

        const body = await exportResponse.json();
        expect(body).toHaveProperty('url');
        expect(body).toHaveProperty('filename');
        expect(body.filename).toMatch(/^trial_balance_financial_report_.*\.xlsx$/);
    });
});
