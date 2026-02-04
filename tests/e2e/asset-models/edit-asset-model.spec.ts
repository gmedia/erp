import { test, expect } from '@playwright/test';
import { createAssetModel, searchAssetModel, editAssetModel, login } from '../helpers';

test('edit asset model end-to-end', async ({ page }) => {
  await login(page);

  const modelName = await createAssetModel(page, {
    model_name: 'Model To Edit',
    manufacturer: 'Original Manufacturer',
  });

  await page.goto('/asset-models');
  await searchAssetModel(page, modelName);

  await editAssetModel(page, modelName, {
    model_name: 'Updated Model Name',
  });

  await page.waitForTimeout(1000);
  await searchAssetModel(page, 'Updated Model Name');

  const row = page.locator('tr', { hasText: 'Updated Model Name' });
  await expect(row).toBeVisible();
});
