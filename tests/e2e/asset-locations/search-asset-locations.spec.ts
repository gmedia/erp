import { test, expect } from '@playwright/test';
import { createAssetLocation, login, searchAssetLocation } from '../helpers';

test.describe('Asset Location Search', () => {
  test('search asset location by code', async ({ page }) => {
    const uniqueId = Date.now().toString();
    const code = `SRCH-C-${uniqueId}`;
    await createAssetLocation(page, { code });

    await login(page);
    await page.goto('/asset-locations');

    await searchAssetLocation(page, code);

    const row = page.locator('tr').filter({ hasText: code }).first();
    await expect(row).toBeVisible();
  });

  test('search asset location by name', async ({ page }) => {
    const uniqueId = Date.now().toString();
    const name = `Search Test Location ${uniqueId}`;
    await createAssetLocation(page, { name });

    await login(page);
    await page.goto('/asset-locations');

    await searchAssetLocation(page, name);

    const row = page.locator('tr').filter({ hasText: name }).first();
    await expect(row).toBeVisible();
  });
});
