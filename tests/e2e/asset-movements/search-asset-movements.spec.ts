import { test, expect } from '@playwright/test';
import { login, createAsset, createAssetMovement, searchAssetMovement } from '../helpers';

test.describe('Asset Movement Search', () => {
  let assetCode: string;
  let reference: string;

  test.beforeEach(async ({ page }) => {
    await login(page);
    assetCode = await createAsset(page, { name: 'Unique Search Asset' });
    reference = `RF-SEARCH-${Date.now()}`;
    await createAssetMovement(page, {
      asset_id: assetCode,
      movement_type: 'Transfer',
      to_branch_id: 'Head Office',
      to_location_id: 'Warehouse',
      reference,
    });
  });

  test('should search by reference', async ({ page }) => {
    await page.goto('/asset-movements');
    await searchAssetMovement(page, reference);
    await expect(page.locator('tr', { hasText: reference }).first()).toBeVisible();
  });

  test('should search by asset name', async ({ page }) => {
    await page.goto('/asset-movements');
    await searchAssetMovement(page, 'Unique Search Asset');
    await expect(page.locator('tr', { hasText: /Unique Search Asset/i }).first()).toBeVisible();
  });
});
