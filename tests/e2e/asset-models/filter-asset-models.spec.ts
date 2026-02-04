import { test, expect } from '@playwright/test';
import { login, createAssetModel } from '../helpers';

test('filter asset models by search', async ({ page }) => {
  await login(page);

  await createAssetModel(page, { model_name: 'Alpha Model' });
  await createAssetModel(page, { model_name: 'Beta Model' });
  const targetName = await createAssetModel(page, { model_name: 'Gamma Model' });

  await page.goto('/asset-models');

  // Search by model name
  await page.fill('input[placeholder="Search by model name or manufacturer..."]', targetName);
  await page.press('input[placeholder="Search by model name or manufacturer..."]', 'Enter');
  await page.waitForLoadState('networkidle');

  await expect(page.locator('tr', { hasText: targetName })).toBeVisible();
  await expect(page.locator('tr', { hasText: 'Alpha Model' })).not.toBeVisible();

  // Search by different term
  await page.fill('input[placeholder="Search by model name or manufacturer..."]', 'Alpha');
  await page.press('input[placeholder="Search by model name or manufacturer..."]', 'Enter');
  await page.waitForLoadState('networkidle');

  await expect(page.locator('tr', { hasText: 'Alpha Model' })).toBeVisible();
  await expect(page.locator('tr', { hasText: targetName })).not.toBeVisible();
});
