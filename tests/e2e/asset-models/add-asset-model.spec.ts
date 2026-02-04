import { test, expect } from '@playwright/test';
import { createAssetModel, searchAssetModel } from '../helpers';

test('add new asset model end-to-end', async ({ page }) => {
  const modelName = await createAssetModel(page, {
    model_name: 'E2E Test Model',
    manufacturer: 'Test Manufacturer',
  });

  await searchAssetModel(page, modelName);

  const row = page.locator('tr', { hasText: modelName });
  await expect(row).toBeVisible();
  await expect(row).toContainText('Test Manufacturer');
});
