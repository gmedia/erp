import { test, expect } from '@playwright/test';
import { login, createAsset, createAssetMovement } from '../helpers';

test.describe('Asset Movement Filtering', () => {
  let assetCode: string;

  test.beforeEach(async ({ page }) => {
    await login(page);
    assetCode = await createAsset(page, { name: 'Filter Test Asset' });
    await createAssetMovement(page, {
      asset_id: assetCode,
      movement_type: 'Transfer',
      to_branch_id: 'Head Office',
      to_location_id: 'Warehouse',
      reference: `FLT-TRF-${Date.now()}`,
    });
    await createAssetMovement(page, {
      asset_id: assetCode,
      movement_type: 'Assign',
      to_department_id: 'Engineering',
      to_employee_id: 'Admin User',
      reference: `FLT-ASN-${Date.now()}`,
    });
  });

  test('should filter by movement type', async ({ page }) => {
    await page.goto('/asset-movements');
    
    // Open filter dialog
    await page.getByRole('button', { name: /Filters/i }).click();
    
    // Select Type
    const filterModal = page.getByRole('dialog', { name: /filters/i });
    const typeTrigger = filterModal.getByRole('combobox').filter({ hasText: /Filter by type/i });
    await typeTrigger.click();
    await page.getByRole('option', { name: 'Transfer', exact: true }).click();
    
    // Apply filters
    await page.getByRole('button', { name: /Apply/i }).click();

    await expect(page.locator('tr', { hasText: /Transfer/i }).first()).toBeVisible();
    await expect(page.locator('tr', { hasText: /Assign/i })).not.toBeVisible();
  });

  test('should filter by asset', async ({ page }) => {
    await page.goto('/asset-movements');
    
    await page.getByRole('button', { name: /Filters/i }).click();
    
    // Select Asset
    const filterModal = page.getByRole('dialog', { name: /filters/i });
    const assetTrigger = filterModal.getByRole('combobox').filter({ hasText: /Filter by asset/i });
    await assetTrigger.click();
    
    const searchInput = page.getByPlaceholder('Search...').last();
    await searchInput.fill(assetCode);
    await page.waitForTimeout(1000);
    await page.getByRole('option').first().click();
    
    await page.getByRole('button', { name: /Apply/i }).click();

    await expect(page.locator('tr', { hasText: assetCode }).first()).toBeVisible();
  });
});
