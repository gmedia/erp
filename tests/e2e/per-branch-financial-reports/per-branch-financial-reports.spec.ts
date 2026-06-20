import { test, expect } from '@playwright/test';
import { login } from '../helpers';

type ReportTarget = {
    name: string;
    route: string;
    api: string;
};

const reports: ReportTarget[] = [
    {
        name: 'Balance Sheet',
        route: '/reports/balance-sheet',
        api: '/api/reports/balance-sheet',
    },
    {
        name: 'Trial Balance',
        route: '/reports/trial-balance',
        api: '/api/reports/trial-balance',
    },
    {
        name: 'Cash Flow',
        route: '/reports/cash-flow',
        api: '/api/reports/cash-flow',
    },
];

test.describe('Per-branch financial report filter', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    for (const report of reports) {
        test(`${report.name} scopes the report by branch`, async ({ page }) => {
            test.setTimeout(60000);

            await page.goto(report.route);

            await page.waitForResponse(
                (r) =>
                    r.url().includes(report.api) &&
                    !r.url().includes('/export') &&
                    r.status() < 400,
                { timeout: 30000 },
            );

            const branchSelect = page
                .getByRole('combobox')
                .filter({ hasText: 'All Branches' });
            await expect(branchSelect).toBeVisible();
            await branchSelect.click();

            const firstBranch = page
                .locator('ul[aria-busy="false"] button')
                .first();
            await expect(firstBranch).toBeVisible({ timeout: 10000 });

            const scopedRequest = page.waitForResponse(
                (r) =>
                    r.url().includes(report.api) &&
                    !r.url().includes('/export') &&
                    r.url().includes('branch_id=') &&
                    r.request().method() === 'GET' &&
                    r.status() < 400,
                { timeout: 15000 },
            );
            await firstBranch.click();
            await scopedRequest;

            await expect(page).toHaveURL(/branch_id=\d+/);
        });
    }
});
