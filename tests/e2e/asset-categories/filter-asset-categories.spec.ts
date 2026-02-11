import { test, expect } from '@playwright/test';
import { login, createAssetCategory } from '../helpers';

test('filter asset categories by search', async ({ page }) => {
  await login(page);
  
  const timestamp = Date.now();
  const cat1 = `Alpha ${timestamp}`;
  const cat2 = `Beta ${timestamp}`;
  const cat3 = `Gamma ${timestamp}`;
  const code1 = `F1-${timestamp}`;
  const code2 = `F2-${timestamp}`;
  const code3 = `F3-${timestamp}`;

  await createAssetCategory(page, { code: code1, name: cat1 });
  await createAssetCategory(page, { code: code2, name: cat2 });
  const targetCode = await createAssetCategory(page, { code: code3, name: cat3 });

  await page.goto('/asset-categories');

  // Search by code
  await page.fill('input[placeholder="Search asset categories..."]', targetCode);
  await page.press('input[placeholder="Search asset categories..."]', 'Enter');
  await page.waitForLoadState('networkidle');

  await expect(page.locator('tr', { hasText: targetCode })).toBeVisible();
  await expect(page.locator('tr', { hasText: cat1 })).not.toBeVisible();

  // Search by name
  await page.fill('input[placeholder="Search asset categories..."]', 'Alpha');
  await page.press('input[placeholder="Search asset categories..."]', 'Enter');
  await page.waitForLoadState('networkidle');

  await expect(page.locator('tr', { hasText: cat1 })).toBeVisible();
  await expect(page.locator('tr', { hasText: targetCode })).not.toBeVisible();
});
