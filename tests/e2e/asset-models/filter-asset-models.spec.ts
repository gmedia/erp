import { test, expect } from '@playwright/test';
import { login, createAssetModel } from '../helpers';

test('filter asset models by search', async ({ page }) => {
  await login(page);

  const timestamp = Date.now();
  const alpha = `Alpha Model ${timestamp}`;
  const beta = `Beta Model ${timestamp}`;
  const targetName = `Gamma Model ${timestamp}`;

  await createAssetModel(page, { model_name: alpha });
  await createAssetModel(page, { model_name: beta });
  await createAssetModel(page, { model_name: targetName });

  await page.goto('/asset-models');

  // Search by model name
  await page.fill('input[placeholder="Search by model name or manufacturer..."]', targetName);
  await page.press('input[placeholder="Search by model name or manufacturer..."]', 'Enter');
  await page.waitForLoadState('networkidle');

  await expect(page.locator('tr').filter({ hasText: targetName }).first()).toBeVisible();
  await expect(page.locator('tr').filter({ hasText: alpha })).not.toBeVisible();

  // Search by different term
  await page.fill('input[placeholder="Search by model name or manufacturer..."]', 'Alpha');
  await page.press('input[placeholder="Search by model name or manufacturer..."]', 'Enter');
  await page.waitForLoadState('networkidle');

  await expect(page.locator('tr').filter({ hasText: alpha }).first()).toBeVisible();
  await expect(page.locator('tr').filter({ hasText: targetName })).not.toBeVisible();
});
