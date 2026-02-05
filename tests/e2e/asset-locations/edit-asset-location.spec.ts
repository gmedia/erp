import { test, expect } from '@playwright/test';
import { createAssetLocation, editAssetLocation, searchAssetLocation } from '../helpers';

test('edit asset location end-to-end', async ({ page }) => {
  const originalName = await createAssetLocation(page);
  const updatedName = `Updated Asset Location ${Date.now()}`;

  await editAssetLocation(page, originalName, {
    name: updatedName,
  });

  await searchAssetLocation(page, updatedName);

  const row = page.locator('tr').filter({ hasText: updatedName }).first();
  await expect(row).toBeVisible();
});
