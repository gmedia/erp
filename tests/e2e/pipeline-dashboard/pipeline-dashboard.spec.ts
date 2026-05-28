import { test, expect } from '@playwright/test';
import { login } from '../helpers';

async function waitForPipelineDashboardData(
  page: Parameters<typeof test.beforeEach>[0]['page'],
) {
  await page.waitForResponse(
    (response) =>
      response.url().includes('/api/pipeline-dashboard/data') &&
      response.request().method() === 'GET' &&
      response.status() < 400,
    { timeout: 15000 },
  );
}

test.describe('Pipeline Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('can navigate to pipeline dashboard from admin menu and view core sections', async ({
    page,
  }) => {
    // 1. Open Admin menu and click Pipeline Dashboard
    await page
      .getByRole('button', { name: 'Admin', exact: true })
      .first()
      .click();
    const dataPromise = waitForPipelineDashboardData(page);
    await page
      .getByRole('link', { name: 'Pipeline Dashboard', exact: true })
      .click();

    // 2. Wait for navigation and dashboard data
    await page.waitForURL('**/pipeline-dashboard', { timeout: 15000 });
    await dataPromise;

    // 3. Verify heading and filter controls
    await expect(
      page.getByRole('heading', { name: 'Pipeline Dashboard', exact: true }),
    ).toBeVisible({ timeout: 10000 });
    await expect(page.getByText('Select Pipeline', { exact: true })).toBeVisible();
    await expect(page.getByText('Stale Threshold', { exact: true })).toBeVisible();

    // 4. Verify summary, distribution chart, and stale entities sections render
    await expect(page.locator('[data-slot="card"]').first()).toBeVisible({
      timeout: 10000,
    });
    await expect(
      page.getByText('State Distribution', { exact: true }),
    ).toBeVisible();
    await expect(
      page.getByText('Stale Entities', { exact: true }),
    ).toBeVisible();
  });

  test('can change stale threshold filter', async ({ page }) => {
    const dataPromise = waitForPipelineDashboardData(page);
    await page.goto('/pipeline-dashboard');
    await dataPromise;

    const staleSelect = page.locator('#stale-days');
    await expect(staleSelect).toBeVisible({ timeout: 10000 });
    await staleSelect.click();
    await page.getByRole('option', { name: '14 Days' }).click();
    await expect(staleSelect).toContainText('14 Days');
  });

  test('can change pipeline filter', async ({ page }) => {
    const dataPromise = waitForPipelineDashboardData(page);
    await page.goto('/pipeline-dashboard');
    await dataPromise;

    const pipelineSelect = page.locator('#pipeline-filter');
    await expect(pipelineSelect).toBeVisible({ timeout: 10000 });
    await pipelineSelect.click();
    await expect(
      page.getByRole('option', { name: 'All Active Pipelines' }),
    ).toBeVisible();
  });

  test('displays state distribution chart section', async ({ page }) => {
    const dataPromise = waitForPipelineDashboardData(page);
    await page.goto('/pipeline-dashboard');
    await dataPromise;

    await expect(
      page.getByText('State Distribution', { exact: true }),
    ).toBeVisible({ timeout: 10000 });

    // Chart may have data (conic-gradient or legend items) or show empty state
    const chartElement = page.locator('[style*="conic-gradient"]');
    const legendItems = page.locator('[data-slot="card"]').filter({ hasText: 'State Distribution' }).locator('span');
    const emptyState = page.getByText('No entities found.');

    const hasChart = await chartElement.isVisible().catch(() => false);
    const hasLegend = (await legendItems.count()) > 0;
    const hasEmpty = await emptyState.isVisible().catch(() => false);

    expect(hasChart || hasLegend || hasEmpty).toBeTruthy();
  });
});
