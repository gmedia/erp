import { test, expect } from '@playwright/test';
import { login, createAsset, createAssetMovement, searchAssetMovement } from '../helpers';

test.describe('Asset Movement Deletion', () => {
  let assetCode: string;
  let reference: string;

  test.beforeEach(async ({ page }) => {
    await login(page);
    assetCode = await createAsset(page, { name: 'E2E Asset for Delete Movement' });
    reference = `RF-DEL-${Date.now()}`;
    await createAssetMovement(page, {
      asset_id: assetCode,
      movement_type: 'Transfer',
      to_branch_id: 'Head Office',
      to_location_id: 'Warehouse',
      reference,
      notes: 'Notes for deletion'
    });
  });

  test('should delete an asset movement', async ({ page }) => {
    await page.goto('/asset-movements');
    await searchAssetMovement(page, reference);

    const row = page.locator('tr', { hasText: reference }).first();
    await row.getByRole('button', { name: /Actions/i }).click();
    await page.getByRole('menuitem', { name: /Delete/i }).click();

    const deleteDialog = page.getByRole('alertdialog');
    await expect(deleteDialog).toBeVisible();
    await deleteDialog.getByRole('button', { name: /Delete/i }).click();

    await expect(deleteDialog).not.toBeVisible();
    await searchAssetMovement(page, reference);
    await expect(page.locator('tr', { hasText: reference })).not.toBeVisible();
  });
});
