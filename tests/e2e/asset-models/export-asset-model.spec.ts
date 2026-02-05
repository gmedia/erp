import { test, expect } from '@playwright/test';
import { login, createAssetModel } from '../helpers';

test('export asset models', async ({ page }) => {
  await login(page);

  const timestamp = Date.now();
  const modelName = await createAssetModel(page, { model_name: `Export Test Model ${timestamp}` });

  await page.goto('/asset-models');
  await page.waitForLoadState('networkidle');

  // Click the export button
  const exportButton = page.getByRole('button', { name: /Export/i });
  await expect(exportButton).toBeVisible();

  // Start waiting for download before clicking
  const downloadPromise = page.waitForEvent('download', { timeout: 30000 });
  await exportButton.click();

  // Wait for the download to finish
  const download = await downloadPromise;

  // Verify the filename contains expected pattern
  expect(download.suggestedFilename()).toContain('asset_models_export_');
  expect(download.suggestedFilename()).toContain('.xlsx');
});
