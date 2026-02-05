import { test, expect } from '@playwright/test';
import { createAssetModel, searchAssetModel, editAssetModel, login } from '../helpers';

test('edit asset model end-to-end', async ({ page }) => {
  await login(page);

  const timestamp = Date.now();
  const modelName = await createAssetModel(page, {
    model_name: `Model To Edit ${timestamp}`,
    manufacturer: 'Original Manufacturer',
  });

  await page.goto('/asset-models');
  await searchAssetModel(page, modelName);

  const updatedName = `Updated Model Name ${timestamp}`;
  await editAssetModel(page, modelName, {
    model_name: updatedName,
  });

  await page.waitForTimeout(1000);
  await searchAssetModel(page, updatedName);
  
  const row = page.locator('tr', { hasText: updatedName }).first();
  await expect(row).toBeVisible();
});
