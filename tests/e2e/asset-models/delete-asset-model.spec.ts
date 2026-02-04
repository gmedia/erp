import { test, expect } from '@playwright/test';
import { createAssetModel, searchAssetModel, deleteAssetModel, login } from '../helpers';

test('delete asset model end-to-end', async ({ page }) => {
  await login(page);

  const modelName = await createAssetModel(page, {
    model_name: 'Model To Delete',
  });

  await page.goto('/asset-models');
  await searchAssetModel(page, modelName);

  await deleteAssetModel(page, modelName);

  // Verify the model is deleted
  await page.fill('input[placeholder="Search asset models..."]', modelName);
  await page.press('input[placeholder="Search asset models..."]', 'Enter');
  await page.waitForLoadState('networkidle');

  const row = page.locator('tr', { hasText: modelName });
  await expect(row).not.toBeVisible();
});
