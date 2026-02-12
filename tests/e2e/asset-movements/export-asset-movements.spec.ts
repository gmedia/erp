import { test, expect } from '@playwright/test';
import { login, createAsset, createAssetMovement } from '../helpers';

test.describe('Asset Movement Export', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    const assetCode = await createAsset(page, { name: 'Export Test Asset' });
    await createAssetMovement(page, {
      asset_id: assetCode,
      movement_type: 'Transfer',
      reference: `EXP-${Date.now()}`,
    });
  });

  test('should export asset movements to excel', async ({ page }) => {
    await page.goto('/asset-movements');

    // Start waiting for download before clicking. Note no await.
    const downloadPromise = page.waitForEvent('download');
    
    await page.getByRole('button', { name: /Export/i }).click();
    
    const download = await downloadPromise;

    // Wait for the download process to complete and save the downloaded file somewhere.
    expect(download.suggestedFilename()).toContain('asset-movements');
    expect(download.suggestedFilename()).toContain('.xlsx');
  });
});
