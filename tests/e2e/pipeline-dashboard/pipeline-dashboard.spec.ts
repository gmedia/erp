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
});
