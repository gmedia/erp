import { test, expect } from '@playwright/test';
import { login, createAsset, createAssetMovement, searchAssetMovement } from '../helpers';

test.describe('Asset Movement Viewing', () => {
  let assetCode: string;
  let reference: string;

  test.beforeEach(async ({ page }) => {
    await login(page);
    assetCode = await createAsset(page, { name: 'E2E Asset for View Movement' });
    reference = `RF-VIEW-${Date.now()}`;
    await createAssetMovement(page, {
      asset_id: assetCode,
      movement_type: 'Transfer',
      to_branch_id: 'Head Office',
      to_location_id: 'Warehouse',
      reference,
      notes: 'Notes for viewing'
    });
  });

  test('should view asset movement details', async ({ page }) => {
    await page.goto('/asset-movements');
    await searchAssetMovement(page, reference);

    const row = page.locator('tr', { hasText: reference }).first();
    await row.getByRole('button', { name: /Actions/i }).click();
    
    const viewButton = page.getByRole('menuitem', { name: /View/i });
    await expect(viewButton).toBeVisible();
    await viewButton.click();

    const dialog = page.getByRole('dialog').filter({ hasText: /Movement Details/i });
    await expect(dialog).toBeVisible({ timeout: 10000 });
    await expect(dialog.locator('text=Notes for viewing')).toBeVisible();
    await expect(dialog.locator(`text=${reference}`)).toBeVisible();

    await dialog.getByRole('button', { name: 'Close' }).last().click();
    await expect(dialog).not.toBeVisible();
  });
});
