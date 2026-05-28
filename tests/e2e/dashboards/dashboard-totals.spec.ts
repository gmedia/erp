import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Dashboard', () => {
  test('dashboard shows totals cards', async ({ page }) => {
    await login(page, undefined, undefined, { requireDashboard: false });

    await page.goto('/dashboard');

    const cards = [
      { label: 'Total Customer' },
      { label: 'Total Employee' },
      { label: 'Total Supplier' },
      { label: 'Total Asset' },
    ];

    for (const card of cards) {
      const container = page
        .locator('[data-slot="card"]')
        .filter({ hasText: card.label })
        .first();

      await expect(container).toBeVisible({ timeout: 10000 });

      const value = container.locator('[data-slot="card-content"]').first();
      await expect(value).toBeVisible();
      await expect(value).toHaveText(/[\d.,]+/);
    }
  });

  test('can navigate to dashboard from sidebar', async ({ page }) => {
    await login(page, undefined, undefined, { requireDashboard: false });

    await page.goto('/fiscal-years');
    await page.waitForLoadState('networkidle');

    const sidebarLink = page.getByRole('link', { name: 'Dashboard' }).first();
    await sidebarLink.click();

    await page.waitForResponse(
      (r) => r.url().includes('/api/dashboard') && r.status() < 400,
    );

    await expect(page).toHaveURL(/\/dashboard/);
  });

  test('dashboard cards display numeric values', async ({ page }) => {
    await login(page, undefined, undefined, { requireDashboard: false });

    await page.goto('/dashboard');
    await page.waitForResponse(
      (r) => r.url().includes('/api/dashboard') && r.status() < 400,
    );

    const cardLabels = [
      'Total Customer',
      'Total Employee',
      'Total Supplier',
      'Total Asset',
    ];

    for (const label of cardLabels) {
      const card = page
        .locator('[data-slot="card"]')
        .filter({ hasText: label })
        .first();

      await expect(card).toBeVisible({ timeout: 10000 });

      const content = card.locator('[data-slot="card-content"]').first();
      await expect(content).not.toHaveText('—', { timeout: 15000 });
      await expect(content).toHaveText(/^\d[\d.,]*$/);
    }
  });

  test('dashboard page has correct title', async ({ page }) => {
    await login(page, undefined, undefined, { requireDashboard: false });

    await page.goto('/dashboard');
    await page.waitForResponse(
      (r) => r.url().includes('/api/dashboard') && r.status() < 400,
    );

    await expect(page).toHaveTitle(/Dashboard/, { timeout: 10000 });
  });
});
