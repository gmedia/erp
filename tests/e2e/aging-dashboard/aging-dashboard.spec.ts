import { expect, test } from '@playwright/test';
import { login } from '../helpers';

async function waitForAgingDashboardData(
    page: Parameters<typeof test.beforeEach>[0]['page'],
) {
    await page.waitForSelector('[data-slot="card"]', { timeout: 30000 });
}

test.describe('Aging Dashboard', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('can navigate to aging dashboard and view summary cards', async ({
        page,
    }) => {
        await page.goto('/aging-dashboard');
        await waitForAgingDashboardData(page);

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
        await page.goto('/aging-dashboard');
        await waitForAgingDashboardData(page);

        await expect(page.getByText('Receivables (AR)')).toBeVisible();
        await expect(page.getByText('Payables (AP)')).toBeVisible();
    });

    test('displays top overdue customers and suppliers tables', async ({
        page,
    }) => {
        await page.goto('/aging-dashboard');
        await waitForAgingDashboardData(page);

        await expect(page.getByText('Top Overdue Customers')).toBeVisible();
        await expect(page.getByText('Top Overdue Suppliers')).toBeVisible();
    });

    test('can change as_of_date filter and refetch', async ({ page }) => {
        await page.goto('/aging-dashboard');
        await waitForAgingDashboardData(page);

        const dateTrigger = page
            .locator('button')
            .filter({ has: page.locator('.lucide-calendar') });
        await dateTrigger.click();

        const calendar = page.locator('[data-slot="calendar"]');
        await calendar.waitFor({ state: 'visible' });
        await calendar
            .locator('button:not([disabled])')
            .first()
            .click();

        // Wait for URL to reflect the new as_of_date param before asserting.
        // waitForAgingDashboardData resolves too early because [data-slot="card"]
        // is already visible from the initial render; setSearchParams is async.
        await page.waitForURL(/as_of_date=/, { timeout: 10000 });

        await expect(page).toHaveURL(/as_of_date=/);
    });

    test('can refresh data', async ({ page }) => {
        await page.goto('/aging-dashboard');
        await waitForAgingDashboardData(page);

        await page.getByRole('button', { name: 'Refresh Data' }).click();
        await waitForAgingDashboardData(page);

        await expect(
            page.getByRole('heading', { name: 'Accounts Aging Overview' }),
        ).toBeVisible();
    });

    test('can change branch filter and refetch', async ({ page }) => {
        await page.goto('/aging-dashboard');
        await waitForAgingDashboardData(page);

        const branchTrigger = page.getByRole('combobox', { name: 'Branch' });
        await branchTrigger.click();

        const firstBranchOption = page
            .getByRole('option')
            .filter({ hasNotText: 'All Branches' })
            .first();
        await firstBranchOption.click();

        await waitForAgingDashboardData(page);

        await expect(page).toHaveURL(/branch_id=\d+/);
    });

    test('renders five aging buckets per chart', async ({ page }) => {
        await page.goto('/aging-dashboard');
        await waitForAgingDashboardData(page);

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
