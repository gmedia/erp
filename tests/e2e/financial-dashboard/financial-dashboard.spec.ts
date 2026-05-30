import { test, expect } from '@playwright/test';
import { login } from '../helpers';

async function waitForFinancialDashboardData(page: Parameters<typeof test.beforeEach>[0]['page']) {
  await page.waitForResponse(
    response =>
      response.url().includes('/api/financial-dashboard') &&
      response.request().method() === 'GET' &&
      response.status() < 400,
    { timeout: 15000 },
  );
}

test.describe('Financial Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('can navigate to financial dashboard and view KPI cards', async ({ page }) => {
    const dataPromise = waitForFinancialDashboardData(page);
    await page.goto('/financial-dashboard');
    await dataPromise;

    await expect(page.getByRole('heading', { name: 'Financial Overview' })).toBeVisible();

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
    await page.getByRole('button', { name: 'Refresh Data' }).click();
    await refreshPromise;

    await expect(page.getByRole('heading', { name: 'Financial Overview' })).toBeVisible();
  });
});
