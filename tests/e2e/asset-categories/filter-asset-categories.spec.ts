import { test, expect } from '@playwright/test';
import { login, createAssetCategory } from '../helpers';

test('filter asset categories by search', async ({ page }) => {
  await login(page);
  
  await createAssetCategory(page, { code: 'FILTER1', name: 'Alpha Category' });
  await createAssetCategory(page, { code: 'FILTER2', name: 'Beta Category' });
  const targetCode = await createAssetCategory(page, { code: 'FILTER3', name: 'Gamma Category' });

  await page.goto('/asset-categories');

  // Search by code
  await page.fill('input[placeholder="Search asset categories..."]', targetCode);
  await page.press('input[placeholder="Search asset categories..."]', 'Enter');
  await page.waitForLoadState('networkidle');

  await expect(page.locator('tr', { hasText: targetCode })).toBeVisible();
  await expect(page.locator('tr', { hasText: 'Alpha Category' })).not.toBeVisible();

  // Search by name
  await page.fill('input[placeholder="Search asset categories..."]', 'Alpha');
  await page.press('input[placeholder="Search asset categories..."]', 'Enter');
  await page.waitForLoadState('networkidle');

  await expect(page.locator('tr', { hasText: 'Alpha Category' })).toBeVisible();
  await expect(page.locator('tr', { hasText: targetCode })).not.toBeVisible();
});
