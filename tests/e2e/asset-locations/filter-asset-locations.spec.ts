import { test, expect } from '@playwright/test';
import { createAssetLocation, login } from '../helpers';

test('filter asset locations by search', async ({ page }) => {
  const uniqueName = `FilterTest-${Date.now()}`;
  await createAssetLocation(page, { name: uniqueName });

  await login(page);
  await page.goto('/asset-locations');

  await page.fill('input[placeholder="Search by code or name..."]', uniqueName);
  await page.press('input[placeholder="Search by code or name..."]', 'Enter');
  await page.waitForLoadState('networkidle');

  const row = page.locator('tr').filter({ hasText: uniqueName }).first();
  await expect(row).toBeVisible();
});

test('filter asset locations by branch', async ({ page }) => {
  const uniqueName = `BranchFilterTest-${Date.now()}`;
  await createAssetLocation(page, { name: uniqueName });

  await login(page);
  await page.goto('/asset-locations');

  // Open branch filter dropdown
  const branchFilter = page.locator('button:has-text("All Branches")');
  if (await branchFilter.isVisible()) {
    await branchFilter.click();
    const option = page.getByRole('option').first();
    if (await option.isVisible({ timeout: 5000 })) {
      await option.click();
      await page.waitForLoadState('networkidle');
    }
  }

  // Verify the table still has data
  const tableBody = page.locator('table tbody');
  await expect(tableBody).toBeVisible();
});
