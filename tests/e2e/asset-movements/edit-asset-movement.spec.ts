import { test, expect } from '@playwright/test';
import { login, createAsset, createAssetMovement, searchAssetMovement } from '../helpers';

test.describe('Asset Movement Editing', () => {
  let assetCode: string;
  let reference: string;

  test.beforeEach(async ({ page }) => {
    await login(page);
    assetCode = await createAsset(page, { name: 'E2E Asset for Edit Movement' });
    reference = `RF-EDIT-${Date.now()}`;
    await createAssetMovement(page, {
      asset_id: assetCode,
      movement_type: 'Transfer',
      to_branch_id: 'Head Office',
      to_location_id: 'Warehouse',
      reference,
      notes: 'Initial notes'
    });
  });

  test('should edit an asset movement notes', async ({ page }) => {
    await page.goto('/asset-movements');
    await searchAssetMovement(page, reference);

    const row = page.locator('tr', { hasText: reference }).first();
    await row.getByRole('button', { name: /Actions/i }).click();
    await page.getByRole('menuitem', { name: /Edit/i }).click();

    const dialog = page.getByRole('dialog');
    await expect(dialog).toBeVisible();

    const newNotes = 'Updated notes via E2E';
    await dialog.locator('textarea[name="notes"]').fill(newNotes);
    await dialog.getByRole('button', { name: /Update Movement/i }).click();

    await expect(dialog).not.toBeVisible();
    await searchAssetMovement(page, reference);
    await expect(page.locator('tr', { hasText: newNotes })).toBeVisible();
  });
});
