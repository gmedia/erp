import { test, expect } from '@playwright/test';
import { login, expectResponseOk } from '../helpers';

async function waitForFinancialDashboardData(page: Parameters<typeof test.beforeEach>[0]['page']) {
  const response = await page.waitForResponse(
    response =>
      response.url().includes('/api/financial-dashboard') &&
      response.request().method() === 'GET',
    { timeout: 15000 },
  );
  await expectResponseOk(response);
}

test.describe('Financial Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('can navigate to financial dashboard and view KPI cards', async ({ page }) => {
    const dataPromise = waitForFinancialDashboardData(page);
    await page.goto('/financial-dashboard');
    await dataPromise;

    await expect(page.getByRole('heading', { name: 'Financial Dashboard' })).toBeVisible();

    const fiscalYearTrigger = page.locator('#fiscal-year-select');
    await expect(fiscalYearTrigger).toBeVisible({ timeout: 10000 });
    await expect(fiscalYearTrigger).not.toHaveText(/Select fiscal year/i);

    const cards = ['Revenue', 'Expenses', 'Net Income', 'Total Assets', 'Total Liabilities', 'Equity', 'Cash Balance'];

    for (const cardLabel of cards) {
      const container = page
        .locator('[data-slot="card"]')
        .filter({ hasText: cardLabel })
        .first();
      await expect(container).toBeVisible({ timeout: 10000 });
    }
  });

  test('displays cash flow summary section', async ({ page }) => {
    const dataPromise = waitForFinancialDashboardData(page);
    await page.goto('/financial-dashboard');
    await dataPromise;

    await expect(page.getByText('Cash Flow Summary')).toBeVisible();
  });

  test('displays expense breakdown section', async ({ page }) => {
    const dataPromise = waitForFinancialDashboardData(page);
    await page.goto('/financial-dashboard');
    await dataPromise;

    await expect(page.getByText('Top Expenses')).toBeVisible();
  });

  test('can refresh data', async ({ page }) => {
    const dataPromise = waitForFinancialDashboardData(page);
    await page.goto('/financial-dashboard');
    await dataPromise;

    const refreshPromise = waitForFinancialDashboardData(page);
    await page.getByRole('button', { name: 'Refresh', exact: true }).click();
    await refreshPromise;

    await expect(page.getByRole('heading', { name: 'Financial Dashboard' })).toBeVisible();
  });
});
