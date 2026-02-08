import { test, expect } from '@playwright/test';
import { login, createAsset } from '../helpers';

test('view asset profile end-to-end', async ({ page }) => {
  // Create a fresh asset to view
  const assetCode = await createAsset(page);

  await page.goto('/assets');
  await page.waitForLoadState('networkidle');

  // Search for the asset
  const searchInput = page.getByPlaceholder(/Search assets.../i);
  await searchInput.fill(assetCode);
  await searchInput.press('Enter');
  await page.waitForLoadState('networkidle');

  // Find the asset in the table and click its name
  const firstRow = page.locator('tbody tr').filter({ hasText: assetCode }).first();
  await expect(firstRow).toBeVisible({ timeout: 10000 });
  
  // Click the name (usually the second column)
  await firstRow.locator('td').nth(2).click();

  // Verify URL and header
  await page.waitForURL(/\/assets\/\w+/);
  await page.waitForLoadState('networkidle');
  // Verify we are on the profile page
  await expect(page.locator('h1')).toContainText(/Test Asset/);
  await expect(page.locator('body')).toContainText(assetCode);

  // Verify basic info is visible in the Summary tab
  await expect(page.getByTestId('summary-general-info')).toBeVisible();
  await expect(page.getByTestId('summary-location-info')).toBeVisible();
  await expect(page.getByTestId('summary-financial-info')).toBeVisible();
  
  await expect(page.getByText(/General Information/i)).toBeVisible();
  await expect(page.getByText(/Current Location & PIC/i)).toBeVisible();

  // Click through all tabs
  const tabs = ['Movements', 'Maintenance', 'Stocktake', 'Depreciation'];
  for (const tabName of tabs) {
    const tabTrigger = page.getByRole('tab', { name: tabName });
    await tabTrigger.click();
    await expect(tabTrigger).toHaveAttribute('aria-selected', 'true');
    // Verify content loads (even if empty, the header should be there)
    await expect(page.locator(`[role="tabpanel"][data-state="active"]`)).toBeVisible();
  }
});
