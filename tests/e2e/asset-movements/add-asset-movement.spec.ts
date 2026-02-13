import { test, expect } from '@playwright/test';
import { login, createAsset, createAssetMovement, searchAssetMovement } from '../helpers';

test.describe('Asset Movement Creation', () => {
  let assetCode: string;

  test.beforeEach(async ({ page }) => {
    await login(page);
    // Create an asset if it doesn't exist, or use a known one. 
    // For reliability, we create a new one.
    assetCode = await createAsset(page, { name: 'E2E Asset for Movement' });
  });

  test('should create a transfer movement', async ({ page }) => {
    const reference = `TRF-${Date.now()}`;
    await createAssetMovement(page, {
      asset_id: assetCode,
      movement_type: 'Transfer',
      to_branch_id: 'Head Office',
      to_location_id: 'Warehouse',
      reference,
      notes: 'Test transfer movement'
    });

    await page.goto('/asset-movements');
    await searchAssetMovement(page, reference);
    await expect(page.locator('tr', { hasText: reference })).toBeVisible();
  });

  test('should create an assignment movement', async ({ page }) => {
    const reference = `ASN-${Date.now()}`;
    await createAssetMovement(page, {
      asset_id: assetCode,
      movement_type: 'Assign',
      to_department_id: 'Engineering',
      to_employee_id: 'Admin User',
      reference,
      notes: 'Test assign movement'
    });

    await page.goto('/asset-movements');
    await searchAssetMovement(page, reference);
    await expect(page.locator('tr', { hasText: reference })).toBeVisible();
  });
});
