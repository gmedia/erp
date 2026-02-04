import { test, expect } from '@playwright/test';
import { createAssetCategory, editAssetCategory, searchAssetCategory } from '../helpers';

test('edit existing asset category end-to-end', async ({ page }) => {
  const code = await createAssetCategory(page, {
    name: 'Original Category',
    useful_life_months_default: '36',
  });

  await editAssetCategory(page, code, {
    name: 'Updated Category',
    useful_life_months_default: '48',
  });

  await searchAssetCategory(page, code);

  const row = page.locator('tr', { hasText: code });
  await expect(row).toContainText('Updated Category');
  await expect(row).toContainText('48');
});
