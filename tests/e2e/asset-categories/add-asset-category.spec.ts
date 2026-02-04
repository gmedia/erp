import { test, expect } from '@playwright/test';
import { createAssetCategory, searchAssetCategory } from '../helpers';

test('add new asset category end-to-end', async ({ page }) => {
  const code = await createAssetCategory(page, {
    name: 'E2E Test Category',
    useful_life_months_default: '60',
  });

  await searchAssetCategory(page, code);

  const row = page.locator('tr', { hasText: code });
  await expect(row).toBeVisible();
  await expect(row).toContainText('E2E Test Category');
  await expect(row).toContainText('60');
});
