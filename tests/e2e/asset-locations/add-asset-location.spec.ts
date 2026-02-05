import { test, expect } from '@playwright/test';
import { createAssetLocation, searchAssetLocation } from '../helpers';

test('add new asset location end-to-end', async ({ page }) => {
  const uniqueId = Date.now().toString();
  const name = await createAssetLocation(page, {
    code: `E2E-LOC-${uniqueId}`,
    name: `E2E Test Location ${uniqueId}`,
  });

  await searchAssetLocation(page, name);

  const row = page.locator('tr').filter({ hasText: name }).first();
  await expect(row).toBeVisible();
  await expect(row).toContainText(`E2E-LOC-${uniqueId}`);
});
