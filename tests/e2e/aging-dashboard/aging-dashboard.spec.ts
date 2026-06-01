import { expect, test } from '@playwright/test';
import { login } from '../helpers';

async function waitForAgingDashboardData(
    page: Parameters<typeof test.beforeEach>[0]['page'],
) {
    await page.waitForResponse(
        (response) =>
            response.url().includes('/api/aging-dashboard') &&
            response.request().method() === 'GET' &&
            response.status() < 400,
        { timeout: 15000 },
    );
}

test.describe('Aging Dashboard', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('can navigate to aging dashboard and view summary cards', async ({
        page,
    }) => {
        const dataPromise = waitForAgingDashboardData(page);
        await page.goto('/aging-dashboard');
        await dataPromise;

        await expect(
            page.getByRole('heading', { name: 'Accounts Aging Overview' }),
        ).toBeVisible();

        const cards = [
            'Total Receivables',
            'AR Overdue',
            'Total Payables',
            'AP Overdue',
        ];

        for (const cardLabel of cards) {
            const container = page
                .locator('[data-slot="card"]')
                .filter({ hasText: cardLabel })
                .first();
            await expect(container).toBeVisible({ timeout: 10000 });
        }
    });

    test('displays AR and AP bucket charts', async ({ page }) => {
        const dataPromise = waitForAgingDashboardData(page);
        await page.goto('/aging-dashboard');
        await dataPromise;

        await expect(page.getByText('Receivables (AR)')).toBeVisible();
        await expect(page.getByText('Payables (AP)')).toBeVisible();
    });

    test('displays top overdue customers and suppliers tables', async ({
        page,
    }) => {
        const dataPromise = waitForAgingDashboardData(page);
        await page.goto('/aging-dashboard');
        await dataPromise;

        await expect(page.getByText('Top Overdue Customers')).toBeVisible();
        await expect(page.getByText('Top Overdue Suppliers')).toBeVisible();
    });

    test('can change as_of_date filter and refetch', async ({ page }) => {
        const dataPromise = waitForAgingDashboardData(page);
        await page.goto('/aging-dashboard');
        await dataPromise;

        const refetchPromise = waitForAgingDashboardData(page);
        await page.locator('#as-of-date-input').fill('2026-01-01');
        await refetchPromise;

        await expect(page).toHaveURL(/as_of_date=2026-01-01/);
    });

    test('can refresh data', async ({ page }) => {
        const dataPromise = waitForAgingDashboardData(page);
        await page.goto('/aging-dashboard');
        await dataPromise;

        const refreshPromise = waitForAgingDashboardData(page);
        await page.getByRole('button', { name: 'Refresh Data' }).click();
        await refreshPromise;

        await expect(
            page.getByRole('heading', { name: 'Accounts Aging Overview' }),
        ).toBeVisible();
    });

    test('can change branch filter and refetch', async ({ page }) => {
        const dataPromise = waitForAgingDashboardData(page);
        await page.goto('/aging-dashboard');
        await dataPromise;

        const refetchPromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/aging-dashboard') &&
                response.url().includes('branch_id=') &&
                response.status() < 400,
            { timeout: 15000 },
        );

        await page.locator('#branch-select').click();
        const firstBranchOption = page
            .getByRole('option')
            .filter({ hasNotText: 'All Branches' })
            .first();
        await firstBranchOption.click();
        await refetchPromise;

        await expect(page).toHaveURL(/branch_id=\d+/);
    });

    test('renders five aging buckets per chart', async ({ page }) => {
        const dataPromise = waitForAgingDashboardData(page);
        await page.goto('/aging-dashboard');
        await dataPromise;

        const expectedLabels = [
            'Current',
            '1-30 Days',
            '31-60 Days',
            '61-90 Days',
            'Over 90 Days',
        ];

        for (const label of expectedLabels) {
            await expect(
                page.getByText(label, { exact: true }).first(),
            ).toBeVisible();
        }
    });
});
